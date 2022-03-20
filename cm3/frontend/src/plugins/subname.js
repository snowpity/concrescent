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
  if (typeof value.name_on_badge == "undefined" && typeof value['name-on-badge'] == "string")
    value.name_on_badge = value['name-on-badge'];
  if (typeof value.real_name == "undefined" && typeof value['real-name'] == "string")
    value.real_name = value['real-name'];
  if (typeof value.fandom_name == "undefined" && typeof value['fandom-name'] == "string")
    value.fandom_name = value['fandom-name'];


  if (!value.fandom_name) {
    //We don't really care, just put the first and last name
    return secondary ? null : value.real_name;
  }
  var name_on_badge = value.name_on_badge;
  //default it if not set
  if (name_on_badge == null) {
    name_on_badge = "Fandom Name Large, Real Name Small"
  }

  if (!secondary) {

    switch (name_on_badge) {
      case "Fandom Name Large, Real Name Small":
      case "Fandom Name Only":
        return value.fandom_name;
      case "Real Name Large, Fandom Name Small":
      case "Real Name Only":
        return value.real_name;
    }
  } else {

    switch (name_on_badge) {
      case "Fandom Name Large, Real Name Small":
        return value.real_name;
      case "Real Name Large, Fandom Name Small":
        return value.fandom_name;
      case "Real Name Only":
      case "Fandom Name Only":
        return null;
    }
  }

}
