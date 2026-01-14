export const capitalize = (str) => {
  if (str == null || str === '') return str;
  return str.charAt(0).toUpperCase() + str.slice(1);
};
