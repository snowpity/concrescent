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
  if (typeof value == "undefined" || value == null)
    return null;
  //Fixup raw DB values if the corrected values don't exist
  if (typeof value.nameDisplay == "undefined" && typeof value['name-on-badge'] == "string")
    value.nameDisplay = value['name-on-badge'];
  if (typeof value.nameFirst == "undefined" && typeof value['first-name'] == "string")
    value.nameFirst = value['first-name'];
  if (typeof value.nameLast == "undefined" && typeof value['last-name'] == "string")
    value.nameLast = value['last-name'];
  if (typeof value.nameFandom == "undefined" && typeof value['fandom-name'] == "string")
    value.nameFandom = value['fandom-name'];


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
