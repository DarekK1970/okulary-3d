const encoder = new TextEncoder();

const ascii = (value) => encoder.encode(value);

const escapePdfString = (value) => String(value)
    .replace(/\\/g, '\\\\')
    .replace(/\(/g, '\\(')
    .replace(/\)/g, '\\)');

const mmToPt = (mm) => (mm * 72) / 25.4;

const dataUrlToBytes = (dataUrl) => {
    const base64 = dataUrl.split(',')[1] || '';
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);

    for (let i = 0; i < binary.length; i += 1) {
        bytes[i] = binary.charCodeAt(i);
    }

    return bytes;
};

const estimateCenteredTextX = (text, pageWidthPt, fontSizePt) => {
    const estimatedWidth = text.length * fontSizePt * 0.46;

    return Math.max(18, (pageWidthPt - estimatedWidth) / 2);
};

const createPdfBlob = ({
    canvas,
    pageWidthMm,
    pageHeightMm,
    title,
    subject,
    keywords,
    creator = 'Wortal Okulary 3D',
    producer = 'Wortal Okulary 3D PDF Engine',
    headerText = 'www.okulary-3d.pl',
    headerFontSizePt = 10,
    headerTopMarginMm = 4,
    headerReservedAreaMm = 8,
}) => {
    const pageWidthPt = mmToPt(pageWidthMm);
    const pageHeightPt = mmToPt(pageHeightMm);
    const headerTopMarginPt = mmToPt(headerTopMarginMm);
    const headerReservedAreaPt = mmToPt(headerReservedAreaMm);
    const imageHeightPt = Math.max(1, pageHeightPt - headerReservedAreaPt);
    const headerXPt = estimateCenteredTextX(
        headerText,
        pageWidthPt,
        headerFontSizePt
    ).toFixed(2);
    const headerYPt = (
        pageHeightPt - headerTopMarginPt - headerFontSizePt
    ).toFixed(2);
    const jpegBytes = dataUrlToBytes(canvas.toDataURL('image/jpeg', 0.96));

    const contentStream = [
        'BT',
        '/F1 ' + headerFontSizePt + ' Tf',
        headerXPt + ' ' + headerYPt + ' Td',
        '(' + escapePdfString(headerText) + ') Tj',
        'ET',
        'q',
        pageWidthPt.toFixed(2) + ' 0 0 ' + imageHeightPt.toFixed(2) + ' 0 0 cm',
        '/Im0 Do',
        'Q',
        '',
    ].join('\n');

    const objects = [
        ascii('1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n'),
        ascii('2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n'),
        ascii(
            '3 0 obj\n'
            + '<< /Type /Page /Parent 2 0 R '
            + '/MediaBox [0 0 ' + pageWidthPt.toFixed(2) + ' ' + pageHeightPt.toFixed(2) + '] '
            + '/Resources << '
            + '/Font << /F1 7 0 R >> '
            + '/XObject << /Im0 4 0 R >> '
            + '>> '
            + '/Contents 5 0 R >>\n'
            + 'endobj\n'
        ),
        new Blob([
            ascii(
                '4 0 obj\n'
                + '<< /Type /XObject /Subtype /Image '
                + '/Width ' + canvas.width + ' /Height ' + canvas.height + ' '
                + '/ColorSpace /DeviceRGB /BitsPerComponent 8 '
                + '/Filter /DCTDecode /Length ' + jpegBytes.length + ' >>\nstream\n'
            ),
            jpegBytes,
            ascii('\nendstream\nendobj\n'),
        ]),
        ascii(
            '5 0 obj\n'
            + '<< /Length ' + contentStream.length + ' >>\n'
            + 'stream\n'
            + contentStream
            + 'endstream\n'
            + 'endobj\n'
        ),
        ascii(
            '6 0 obj\n'
            + '<< '
            + '/Title (' + escapePdfString(title) + ') '
            + '/Subject (' + escapePdfString(subject) + ') '
            + '/Keywords (' + escapePdfString(keywords) + ') '
            + '/Creator (' + escapePdfString(creator) + ') '
            + '/Producer (' + escapePdfString(producer) + ') '
            + '>>\n'
            + 'endobj\n'
        ),
        ascii('7 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n'),
    ];

    const header = ascii('%PDF-1.4\n%\xFF\xFF\xFF\xFF\n');
    const parts = [header];
    const offsets = [0];
    let position = header.length;

    objects.forEach((objectPart) => {
        offsets.push(position);

        if (objectPart instanceof Blob) {
            parts.push(objectPart);
            position += objectPart.size;
        } else {
            parts.push(objectPart);
            position += objectPart.length;
        }
    });

    const xrefStart = position;
    let xref = 'xref\n0 8\n';
    xref += '0000000000 65535 f \n';

    for (let i = 1; i <= 7; i += 1) {
        xref += `${String(offsets[i]).padStart(10, '0')} 00000 n \n`;
    }

    const trailer = (
        'trailer\n'
        + '<< /Size 8 /Root 1 0 R /Info 6 0 R >>\n'
        + 'startxref\n'
        + `${xrefStart}\n`
        + '%%EOF'
    );

    parts.push(ascii(xref));
    parts.push(ascii(trailer));

    return new Blob(parts, { type: 'application/pdf' });
};

const downloadBlob = (blob, filename) => {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = filename;

    document.body.appendChild(link);
    link.click();
    link.remove();

    window.setTimeout(() => {
        URL.revokeObjectURL(url);
    }, 1200);
};

export const downloadCanvasAsPdf = ({
    canvas,
    pageWidthMm,
    pageHeightMm,
    filename,
    title,
    subject,
    keywords,
}) => {
    const blob = createPdfBlob({
        canvas,
        pageWidthMm,
        pageHeightMm,
        title,
        subject,
        keywords,
    });

    downloadBlob(blob, filename);
};

export const downloadCanvasAsPng = ({
    canvas,
    filename,
}) => {
    canvas.toBlob((blob) => {
        if (!blob) {
            return;
        }

        downloadBlob(blob, filename);
    }, 'image/png');
};
