export function split_carriagereturn(value) {
  if (typeof value != "string")
    return null;
  return value.split("\n");
}