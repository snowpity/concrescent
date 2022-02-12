Download dependencies
 php composer.phar  install

 PHP plugins required:

 - sodium
 - mbstring
 - mysqli
 - sodium
 - zlib

Refresh the autoloader classes:
php composer.phar  dump-autoload


Token data, unpacked:

  contact ID, bigint
  Currently selected Event ID int
  Special Permission bytes in the form of bitmask
    Permission set 1
      Attendee view
      Attendee Edit
      Attendee Export
      Attendee types manage, email templates, questions
      Attendee refund
      Badge statistics
    Permission set 2
      Check in
      Print one-off badges
      Manage badge formats
      Manage venue map, locations
      Payment request view
      Payment request Create/Cancel
      Payment request Edit
    Permission set 3
      Manage banlist
      Manage users
      Staff apps view
      Staff apps Review/Assign
      Staff apps Edit
      Staff apps export
      Staff apps manage types (including org chart), email templates, questions
      Badge_Ice (See/edit bade ICE info)
      Contact_Full (See/edit phone and address info)
      Event admin (Manage active events, groups)
      Blocked from applying for badges
      Global admin (access to create events, plugin configuration, etc)

  Application group Permission string, count byte
    Application group ID (or 0 for attendees), int
    Permission byte in the form of a flag enum
      View
      Review/Assign
      Edit
      Export data
      Manage types, email templates, questions
