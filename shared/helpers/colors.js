export function adjustColor(color, lightenAmount, reduceSaturationAmount) {
  const ctx = document.createElement('canvas').getContext('2d');
  ctx.fillStyle = color;
  const rgb = ctx.fillStyle.startsWith('#') ? hexToRgb(ctx.fillStyle) : ctx.fillStyle;

  const [r, g, b] = rgb.match(/\d+/g).map(Number);
  const hsl = rgbToHsl(r, g, b);

  hsl[1] = Math.max(hsl[1] - reduceSaturationAmount, 0.5);
  hsl[2] = Math.min(hsl[2] + lightenAmount, 0.95);

  return hslToRgb(hsl[0], hsl[1], hsl[2]);
}

export function darkenColor(color, amount) {
  const [r, g, b] = color.match(/\d+/g).map(Number);
  return `rgb(${Math.max(r - amount, 0)}, ${Math.max(g - amount, 0)}, ${Math.max(b - amount, 0)})`;
}

export function rgbToHsl(r, g, b) {
  r /= 255; g /= 255; b /= 255;
  const max = Math.max(r, g, b), min = Math.min(r, g, b);
  let h, s, l = (max + min) / 2;

  if (max === min) {
    h = s = 0; // Couleur neutre
  } else {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    switch (max) {
      case r: h = (g - b) / d + (g < b ? 6 : 0); break;
      case g: h = (b - r) / d + 2; break;
      case b: h = (r - g) / d + 4; break;
    }
    h /= 6;
  }

  return [h, s, l];
}

export function hslToRgb(h, s, l) {
  let r, g, b;

  if (s === 0) {
    r = g = b = l; // Couleur neutre
  } else {
    const hue2rgb = (p, q, t) => {
      if (t < 0) t += 1;
      if (t > 1) t -= 1;
      if (t < 1 / 6) return p + (q - p) * 6 * t;
      if (t < 1 / 2) return q;
      if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
      return p;
    };

    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    r = hue2rgb(p, q, h + 1 / 3);
    g = hue2rgb(p, q, h);
    b = hue2rgb(p, q, h - 1 / 3);
  }

  return `rgb(${Math.round(r * 255)}, ${Math.round(g * 255)}, ${Math.round(b * 255)})`;
}

// méthode pour transformer une couleur de son nom textuel (exemple : 'red') en son code RGB
export function colorNameToRgb(colorName) {
  const ctx = document.createElement('canvas').getContext('2d');
  ctx.fillStyle = colorName;
  // La valeur retournée est sous la forme 'rgb(r, g, b)' ou 'rgba(r, g, b, a)'
  return ctx.fillStyle.startsWith('#')
      ? hexToRgb(ctx.fillStyle)
      : ctx.fillStyle;
}

// Helper pour convertir un hex en rgb
export function hexToRgb(hex) {
  let c = hex.substring(1);
  if (c.length === 3) c = c.split('').map(x => x + x).join('');
  const num = parseInt(c, 16);
  return `rgb(${(num >> 16) & 255}, ${(num >> 8) & 255}, ${num & 255}, 1)`;
}
