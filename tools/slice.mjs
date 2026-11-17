// Usage: node tools/slice.mjs <full.png> <outPrefix> [sliceHeight=2400] [outWidth=800]  → outPrefix-p0.png ...
import sharp from 'sharp';

const [file, prefix, sliceH = '2400', outW = '800'] = process.argv.slice(2);
const meta = await sharp(file).metadata();
const h = +sliceH;
const parts = Math.ceil(meta.height / h);
for (let i = 0; i < parts; i += 1) {
	await sharp(file)
		.extract({ left: 0, top: i * h, width: meta.width, height: Math.min(h, meta.height - i * h) })
		.resize({ width: +outW })
		.toFile(`${prefix}-p${i}.png`);
}
console.log(`${file}: ${meta.width}x${meta.height} -> ${parts} slices`);
