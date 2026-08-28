const isStandaloneMarker = (marker) => {
    return marker === 0x01
        || (marker >= 0xd0 && marker <= 0xd7);
};

const findNextMarker = (bytes, offset) => {
    let index = offset;

    while (index < bytes.length - 1) {
        if (bytes[index] !== 0xff) {
            index += 1;
            continue;
        }

        let codeIndex = index + 1;

        while (
            codeIndex < bytes.length
            && bytes[codeIndex] === 0xff
        ) {
            codeIndex += 1;
        }

        if (codeIndex >= bytes.length) {
            return null;
        }

        return {
            markerStart: index,
            marker: bytes[codeIndex],
            codeIndex,
        };
    }

    return null;
};

const findJpegEnd = (bytes, start) => {
    if (
        bytes[start] !== 0xff
        || bytes[start + 1] !== 0xd8
    ) {
        return null;
    }

    let offset = start + 2;

    while (offset < bytes.length - 1) {
        const found = findNextMarker(bytes, offset);

        if (!found) {
            return null;
        }

        const {
            markerStart,
            marker,
            codeIndex,
        } = found;

        if (marker === 0xd9) {
            return codeIndex + 1;
        }

        // Stuffed byte outside scan data — skip defensively.
        if (marker === 0x00) {
            offset = codeIndex + 1;
            continue;
        }

        if (isStandaloneMarker(marker)) {
            offset = codeIndex + 1;
            continue;
        }

        if (codeIndex + 2 >= bytes.length) {
            return null;
        }

        const segmentLength =
            (bytes[codeIndex + 1] << 8)
            | bytes[codeIndex + 2];

        if (segmentLength < 2) {
            return null;
        }

        const segmentEnd =
            codeIndex + 1 + segmentLength;

        if (segmentEnd > bytes.length) {
            return null;
        }

        if (marker !== 0xda) {
            offset = segmentEnd;
            continue;
        }

        // Start of Scan: parse entropy-coded data until an
        // unescaped marker is found. This avoids treating JPEG
        // thumbnails embedded in EXIF/APP segments as MPO images.
        let scanOffset = segmentEnd;

        while (scanOffset < bytes.length - 1) {
            if (bytes[scanOffset] !== 0xff) {
                scanOffset += 1;
                continue;
            }

            let markerIndex = scanOffset + 1;

            while (
                markerIndex < bytes.length
                && bytes[markerIndex] === 0xff
            ) {
                markerIndex += 1;
            }

            if (markerIndex >= bytes.length) {
                return null;
            }

            const scanMarker = bytes[markerIndex];

            if (scanMarker === 0x00) {
                scanOffset = markerIndex + 1;
                continue;
            }

            if (
                scanMarker >= 0xd0
                && scanMarker <= 0xd7
            ) {
                scanOffset = markerIndex + 1;
                continue;
            }

            if (scanMarker === 0xd9) {
                return markerIndex + 1;
            }

            // Progressive JPEG can continue with another marker
            // segment and scan. Return to the marker parser.
            offset = scanOffset;
            break;
        }
    }

    return null;
};

const findNextSoi = (bytes, offset) => {
    for (let i = offset; i < bytes.length - 1; i += 1) {
        if (
            bytes[i] === 0xff
            && bytes[i + 1] === 0xd8
        ) {
            return i;
        }
    }

    return -1;
};

export const splitJpegImages = (arrayBuffer) => {
    const bytes = new Uint8Array(arrayBuffer);
    const images = [];
    let offset = 0;

    while (offset < bytes.length - 1) {
        const start = findNextSoi(bytes, offset);

        if (start < 0) {
            break;
        }

        const end = findJpegEnd(bytes, start);

        if (!end || end <= start) {
            break;
        }

        images.push(
            new Blob(
                [bytes.slice(start, end)],
                { type: 'image/jpeg' }
            )
        );

        offset = end;
    }

    return images;
};

export const decodeImageBlob = async (blob) => {
    if ('createImageBitmap' in window) {
        return await createImageBitmap(
            blob,
            { imageOrientation: 'from-image' }
        );
    }

    return await new Promise((resolve, reject) => {
        const url = URL.createObjectURL(blob);
        const image = new Image();

        image.onload = () => {
            URL.revokeObjectURL(url);
            resolve(image);
        };

        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('Image decode failed'));
        };

        image.src = url;
    });
};

export const downloadBlob = (blob, filename) => {
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

export const drawCover = (
    ctx,
    image,
    width,
    height
) => {
    const sourceRatio = image.width / image.height;
    const targetRatio = width / height;

    let drawWidth;
    let drawHeight;

    if (sourceRatio > targetRatio) {
        drawHeight = height;
        drawWidth = height * sourceRatio;
    } else {
        drawWidth = width;
        drawHeight = width / sourceRatio;
    }

    ctx.drawImage(
        image,
        (width - drawWidth) / 2,
        (height - drawHeight) / 2,
        drawWidth,
        drawHeight
    );
};
