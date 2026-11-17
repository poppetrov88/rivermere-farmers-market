// Extracts clean photographic regions from the design reference as TEMPORARY placeholder images.
// Replace these with properly licensed photography before launch.
import sharp from 'sharp';

const SRC = 'home-page.png';
const OUT = 'assets/images/';
const crops = {
	'placeholder-hero': [448, 68, 576, 424],
	'placeholder-community': [46, 714, 545, 309],
	'placeholder-event': [44, 1058, 206, 96],
	'placeholder-vendor-produce': [42, 1247, 226, 86],
	'placeholder-vendor-dairy': [282, 1247, 226, 86],
	'placeholder-vendor-honey': [524, 1247, 226, 86],
	'placeholder-vendor-crafts': [765, 1247, 226, 86],
};

for (const [name, [left, top, width, height]] of Object.entries(crops)) {
	let img = sharp(SRC).extract({ left, top, width, height });
	if (name === 'placeholder-hero') {
		// Paint over headline remnants on the left edge; the theme draws its own green shape there.
		const patch = await sharp({ create: { width: 90, height: 320, channels: 3, background: '#053120' } }).png().toBuffer();
		img = sharp(await img.png().toBuffer()).composite([{ input: patch, left: 0, top: 0 }]);
	}
	await img.webp({ quality: 82 }).toFile(`${OUT}${name}.webp`);
	console.log(name);
}
