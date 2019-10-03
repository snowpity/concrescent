const subRE = /(.*)\((.*)\)/

export function subname(value, sub) {
  let splitted = value.match(subRE);
  if (splitted == null) {
    if (sub) {
      return null;
    } else {
      return value;
    }
  }

  let canSub = splitted.length > 2;
  return splitted[(sub && canSub) ? 2 : 1];
}

export function badgeDisplayName(value, secondary) {
  if (typeof value == "undefined")
    return null;
  if (!value.nameFandom) {
    //We don't really care, just put the first and last name
    return secondary ? null : value.nameFirst + " " + value.nameLast;
  }
  var nameDisplay = value.nameDisplay;
  //default it if not set
  if (nameDisplay == null) {
    nameDisplay = "Fandom Name Large, Real Name Small"
  }

  if (!secondary) {

    switch (nameDisplay) {
      case "Fandom Name Large, Real Name Small":
      case "Fandom Name Only":
        return value.nameFandom;
      case "Real Name Large, Fandom Name Small":
      case "Real Name Only":
        return value.nameFirst + " " + value.nameLast;
    }
  } else {

    switch (nameDisplay) {
      case "Fandom Name Large, Real Name Small":
        return value.nameFirst + " " + value.nameLast;
      case "Real Name Large, Fandom Name Small":
        return value.nameFandom;
      case "Real Name Only":
      case "Fandom Name Only":
        return null;
    }
  }

}