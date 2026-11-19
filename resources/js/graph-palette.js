// Linear sRGB to OKLab, using the CSS Color 4 conversion matrices.
function candidate(red, green, blue) {
    const [r, g, b] = [red, green, blue].map((byte) => {
        const value = byte / 255;
        return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
    });
    if (0.2126 * r + 0.7152 * g + 0.0722 * b < 0.20) return null;

    const l = Math.cbrt(0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b);
    const m = Math.cbrt(0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b);
    const s = Math.cbrt(0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b);

    return {
        hex: '#' + [red, green, blue].map((byte) => byte.toString(16).padStart(2, '0')).join(''),
        lab: [
            0.2104542553 * l + 0.7936177850 * m - 0.0040720468 * s,
            1.9779984951 * l - 2.4285922050 * m + 0.4505937099 * s,
            0.0259040371 * l + 0.7827717662 * m - 0.8086757660 * s,
        ],
        nearest: Infinity,
    };
}

export function communityPalette(count) {
    const candidates = [];
    for (let r = 0; r <= 255; r += 15) {
        for (let g = 0; g <= 255; g += 15) {
            for (let b = 0; b <= 255; b += 15) {
                const color = candidate(r, g, b);
                if (color) candidates.push(color);
            }
        }
    }

    const palette = [];
    let next = candidates.findIndex(({ hex }) => hex === '#00ffff');

    // Keep each candidate's distance to its nearest chosen color.
    while (palette.length < count && next !== -1) {
        const [chosen] = candidates.splice(next, 1);
        palette.push(chosen.hex);
        let farthest = -1;
        next = -1;

        candidates.forEach((color, index) => {
            const distance = color.lab.reduce((sum, axis, i) => sum + (axis - chosen.lab[i]) ** 2, 0);
            color.nearest = Math.min(color.nearest, distance);
            if (color.nearest > farthest) {
                farthest = color.nearest;
                next = index;
            }
        });
    }

    return palette;
}
