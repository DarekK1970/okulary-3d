const pushWord = (target, value) => {
    target.push(value & 0xff);
    target.push((value >> 8) & 0xff);
};

const asciiBytes = (value) => Array.from(value).map((char) => char.charCodeAt(0));

const build332Palette = () => {
    const palette = [];

    for (let index = 0; index < 256; index += 1) {
        const red = ((index >> 5) & 0x07) * 255 / 7;
        const green = ((index >> 2) & 0x07) * 255 / 7;
        const blue = (index & 0x03) * 255 / 3;

        palette.push(Math.round(red), Math.round(green), Math.round(blue));
    }

    return palette;
};

const rgbaTo332 = (rgba) => {
    const indexed = new Uint8Array(rgba.length / 4);
    let output = 0;

    for (let i = 0; i < rgba.length; i += 4) {
        const red = rgba[i] >> 5;
        const green = rgba[i + 1] >> 5;
        const blue = rgba[i + 2] >> 6;

        indexed[output] = (red << 5) | (green << 2) | blue;
        output += 1;
    }

    return indexed;
};

const lzwEncode = (indices) => {
    const clearCode = 256;
    const endCode = 257;
    const codeSize = 9;
    const output = [];

    let bitBuffer = 0;
    let bitCount = 0;

    const writeCode = (code) => {
        bitBuffer |= code << bitCount;
        bitCount += codeSize;

        while (bitCount >= 8) {
            output.push(bitBuffer & 0xff);
            bitBuffer >>= 8;
            bitCount -= 8;
        }
    };

    // A deliberately simple and robust GIF LZW stream:
    // emit literal palette indices and reset the dictionary
    // before a 10-bit code width would become necessary.
    // This produces somewhat larger GIFs but keeps browser-side
    // encoding deterministic and decoder-compatible.
    const literalGroupSize = 200;

    writeCode(clearCode);

    for (let i = 0; i < indices.length; i += 1) {
        if (i > 0 && i % literalGroupSize === 0) {
            writeCode(clearCode);
        }

        writeCode(indices[i]);
    }

    writeCode(endCode);

    if (bitCount > 0) {
        output.push(bitBuffer & 0xff);
    }

    return new Uint8Array(output);
};

const pushSubBlocks = (target, data) => {
    let offset = 0;

    while (offset < data.length) {
        const length = Math.min(255, data.length - offset);
        target.push(length);

        for (let i = 0; i < length; i += 1) {
            target.push(data[offset + i]);
        }

        offset += length;
    }

    target.push(0);
};

export const encodeGif = ({ width, height, frames, delayMs = 140, loop = true }) => {
    const bytes = [];

    bytes.push(...asciiBytes('GIF89a'));
    pushWord(bytes, width);
    pushWord(bytes, height);

    bytes.push(0xf7, 0x00, 0x00);
    bytes.push(...build332Palette());

    if (loop) {
        bytes.push(
            0x21, 0xff, 0x0b,
            ...asciiBytes('NETSCAPE2.0'),
            0x03, 0x01, 0x00, 0x00, 0x00
        );
    }

    const delayCs = Math.max(2, Math.round(delayMs / 10));

    frames.forEach((rgba) => {
        const indexed = rgbaTo332(rgba);
        const compressed = lzwEncode(indexed);

        bytes.push(0x21, 0xf9, 0x04, 0x04);
        pushWord(bytes, delayCs);
        bytes.push(0x00, 0x00);

        bytes.push(0x2c);
        pushWord(bytes, 0);
        pushWord(bytes, 0);
        pushWord(bytes, width);
        pushWord(bytes, height);
        bytes.push(0x00);

        bytes.push(0x08);
        pushSubBlocks(bytes, compressed);
    });

    bytes.push(0x3b);

    return new Blob([new Uint8Array(bytes)], { type: 'image/gif' });
};
