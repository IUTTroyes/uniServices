export const champEuros = { type: 'number', max: 21474836.47, decimales: 2 };
export const champLitres = { type: 'number', min: 0.001, max: 2147483.647, decimales: 3 };

export const enCentimes = euros => Math.round(euros * 100);
export const enMillilitres = litres => Math.round(litres * 1000);
