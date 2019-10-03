/**
 * Mocking client-server processing
 */
const _products = [{
  "id": 1,
  "order": 3,
  "name": "Crusader Weekend (Ages 12 & Under)",
  "description": "For the youngest fans and foals, BABSCon offers free admission to experience all of our pony-filled fun!\nWe ask that your little Crusader be accompanied by a membership-holding adult at all times while at any part of BABSCon outside of the Crusaders' Clubhouse.",
  "rewards": "A whole weekend's access to the kids-only Crusaders’ Clubhouse, featuring exclusive activities with our Guests of Honor and Community Guests!\nWith an accompanying adult: access to all Mane Events & Panels, Vendor Hall, NEIGHhem Concert, Charity Auction, Silver Span’s Salon, Button’s Arcade, Trixie’s Tabletop Gaming, and the Calming Cove",
  "price": 0,
  "payable_onsite": true,
  "quantity": null,
  "min_age": null,
  "max_age": 12,
}, {
  "id": 2,
  "order": 5,
  "name": "	Ponyville Pony (Adult Weekend)",
  "description": "The Ponyville Pony Membership gets you access to all days of the con. If you're bringing children, be sure to check out the Crusader and Teen Memberships.",
  "rewards": "Complete access to all Mane Events & Panels, Vendor Hall, NEIGHhem Concert, Party Floor, Charity Auction, Silver Span’s Salon, Button’s Arcade, Trixie’s Tabletop Gaming, and the Calming Cove\nBABSCon 2020 Conbook",
  "price": 75,
  "payable_onsite": true,
  "quantity": null,
  "min_age": 18,
  "max_age": null,
}, {
  "id": 4,
  "order": 12,
  "name": "Canterlot Pony",
  "description": "Boost your Regular weekend membership with a smattering of goodies.",
  "rewards": "More rewards coming soon!\n1 Autograph Voucher (a $5 value) for our Guests of Honor\nSponsor Exclusive T Shirt\nSponsor Exclusive Lanyard\nComplete access to all Mane Events & Panels, Vendor Hall, NEIGHhem Concert, Party Floor, Charity Auction, Silver Span’s Salon, Button’s Arcade, Trixie’s Tabletop Gaming, and the Calming Cove\nBABSCon 2020 Conbook",
  "price": 150,
  "payable_onsite": 0,
  "quantity": 150,
  "min_age": null,
  "max_age": null,
}, {
  "id": 5,
  "order": 13,
  "name": "High Society Pony (Sponsor)",
  "description": "Our very best value membership with great features, including our Guest of Honor Meet & Greet, where you enjoy hors d'oeuvres and drinks, chat with our Guests of Honor, and have a great time with your fellow Sponsors.",
  "rewards": "Exclusive Guest of Honor Meet & Greet\nHigh Society Collector’s Pin\n4th Position Front of Line Privileges for all Events and Programming (excludes Autographs)\nA Special Thank You on the BABSCon website for your sponsorship\nDiscord Sponsor Badge and Sponsor Channel Access (discord.gg/BqST7J6)\n1 Autograph Voucher (a $5 value) for our Guests of Honor\nDigital BABScon RPG Book\nSponsor Exclusive T-Shirt\nSponsor Exclusive Lanyard\nComplete access to all Mane Events & Panels,\nVendor Hall, NEIGHhem Concert, Party Floor, Charity Auction, Silver Span’s Salon, Button’s Arcade, Trixie’s Tabletop Gaming, and the Calming Cove\nBABSCon 2020 Conbook",
  "price": 300,
  "payable_onsite": false,
  "quantity": 80,
  "min_age": null,
  "max_age": null,
}, {
  "id": 6,
  "order": 14,
  "name": "Crystal Pony (Sponsor)",
  "description": "Crystal Pony is the perfect membership for those wishing to experience BABSCon on a whole different level.",
  "rewards": "Crystal Pony Exclusive Gift by a fandom artisan\n3rd Position Front of Line Privileges for Autographs\n3rd Position Front of Line Privileges for all Events and Programming\nA Special Thank You in the Conbook for your sponsorship\nExclusive Guest of Honor Meet & Greet\nHigh Society Collector’s Pin\nA Special Thank You on the BABSCon website for your sponsorship\nDiscord Sponsor Badge and Sponsor Channel Access (discord.gg/BqST7J6)\n2 Autograph Vouchers (a $10 value) for our Guests of Honor\nDigital BABScon RPG Book\nSponsor Exclusive Backpack\nSponsor Exclusive T-Shirt\nSponsor Exclusive Lanyard\nComplete access to all Mane Events & Panels, Vendor Hall, NEIGHhem Concert, Party Floor, Charity Auction, Silver Span’s Salon, Button’s Arcade, Trixie’s Tabletop Gaming, and the Calming Cove\nBABSCon 2020 Conbook",
  "price": 500,
  "payable_onsite": false,
  "quantity": 25,
  "min_age": null,
  "max_age": null,
}, {
  "id": 7,
  "order": 15,
  "name": "	Noble Pony (Sponsor)",
  "description": "The Noble Pony membership tier makes you a pony everypony should know. Invite a +1 to the Guest of Honor Meet & Greet. Feel like royalty by having your very own personal convention-planning assistant. And see your name in lights on our Sponsor Thank You screen between panels! Trot in as an attendee, trot out as a celebrity!",
  "rewards": "Noble Pony Exclusive gift by a fandom artisan\n,Charity Reception with BronyChef Day 0 - a Galactic and Noble Pony Exclusive\n,Your name included in our Sponsor Thank You Screen shown between panels\n,A Personal Thank You in our on-stage ceremonies for your sponsorship\n,A Sponsor Liaison at your beck and call! They are your personal convention assistant before, during, and after BABSCon. They handle your questions, concerns, and needs, and guarantee your BABSCon experience is like no other.\n,Bring a Friend to the Guest of Honor Meet & Greet. Friendship and hors d'oeuvres are magic!\n,2nd Position Front of Line Privileges for Autographs\n,2nd Position Front of Line Privileges for all Events and Programming\n,Crystal Pony Exclusive Gift by a fandom artisan\n,A Special Thank You in the Conbook for your sponsorship\n,Exclusive Guest of Honor Meet & Greet\n,High Society Collector’s Pin\n,A Special Thank You on the BABSCon website for your sponsorship\n,Discord Sponsor Badge and Sponsor Channel Access (discord.gg/BqST7J6)\n,4 Autograph Vouchers (a $40 value) for our Guests of Honor\n,Digital BABScon RPG Book\n,Sponsor Exclusive Backpack\n,Sponsor Exclusive T-Shirt\n,Sponsor Exclusive Lanyard\n,Complete access to all Mane Events & Panels, Vendor Hall, NEIGHhem Concert, Party Floor, Charity Auction, Silver Span’s Salon, Button’s Arcade, Trixie’s Tabletop Gaming, and the Calming Cove\n,BABSCon 2020 Conbook",
  "price": 1000,
  "payable_onsite": false,
  "quantity": 0,
  "min_age": null,
  "max_age": null,
}, {
  "id": 8,
  "order": 16,
  "name": "Galactic Pony (Sponsor)",
  "description": "This exclusive membership tier gives you the Best of BABSCon™ and is for those who want to support BABSCon the most! Your generosity ensures we can go above and beyond to make every attendee's experience a spectacular one. With only a handful of this membership tier available each year, these gracious individuals not only support BABSCon in a big way, but find themselves treated to the best and most exceptional experiences we have to offer.",
  "rewards": "Intimate Dinner with select Guests of Honor at a fine Burlingame restaurant only for Galactic Ponies, and BABSCon pays the bill!\nGalactic Pony Exclusive Mascot Plushie\nOne-of-a-kind Galactic Pony gift by a fandom artisan\nCharity Reception with BronyChef Day 0 - Galactic and Noble Pony Exclusive\n1st Position Front of Line Privileges for Autographs\n1st Position Front of Line Privileges for all Events and Programming\nYour name included in our Sponsor Thank You Screen shown between panels\nA Personal Thank You in our on-stage ceremonies for your sponsorship\nNoble Pony Exclusive gift by a fandom artisan\nA Sponsor Liaison at your beck and call! They are your personal convention assistant before, during, and after BABSCon. They handle your questions, concerns, and needs, and guarantee your BABSCon experience is like no other.\nBring a Friend to the Guest of Honor Meet & Greet. Friendship and hors d'oeuvres are magic!\nCrystal Pony Exclusive Gift by a fandom artisan\nA Special Thank You in the Conbook for your sponsorship\nExclusive Guest of Honor Meet & Greet\nA Special Thank You on the BABSCon website for your sponsorship\nHigh Society Collector’s Pin\nDiscord Sponsor Badge and Sponsor Channel Access (discord.gg/BqST7J6)\n6 Autograph Vouchers (a $60 value) for our Guests of Honor\nDigital BABScon RPG Book\nSponsor Exclusive Backpack\nSponsor Exclusive T-Shirt\nSponsor Exclusive Lanyard\nComplete access to all Mane Events & Panels, Vendor Hall, NEIGHhem Concert, Party Floor, Charity Auction, Silver Span’s Salon, Button’s Arcade, Trixie’s Tabletop Gaming, and the Calming Cove\nBABSCon 2020 Conbook",
  "price": 2500,
  "payable_onsite": false,
  "quantity": 12,
  "min_age": null,
  "max_age": null,
}, {
  "id": 9,
  "order": 6,
  "name": "AT DOOR Teen Weekend (Ages 13-17)",
  "description": "For the colts and fillies eager to get their cutie mark and their BABSCon 2018 membership, the Crusader 3-day offers our best pricing and all the fun below.",
  "rewards": "A whole weekend of access to Mane Events, general panels, vendor hall, Trixie's tabletop gaming, Button's arcade, Art Show, NEIGHhem Concert, and charity auction. 2019 Convention Book",
  "price": 50,
  "payable_onsite": true,
  "quantity": null,
  "min_age": 13,
  "max_age": 17,
}, {
  "id": 13,
  "order": 1,
  "name": "Ponyville Pony (Adult Weekend)",
  "description": "The Adult 3-Day Membership gets you 3-day access to con. For children, be sure to check out our Crusader and Foal memberships.",
  "rewards": "3 days of access to Mane Events, general panels, vendor hall, Trixie's tabletop gaming, Button's arcade, Art Show, NEIGHhem Concert, and charity auction. 2020 Convention Book",
  "price": 65,
  "payable_onsite": false,
  "quantity": null,
  "min_age": 18,
  "max_age": null,
}, ]

export default {
  getProducts(cb) {
    setTimeout(() => cb(_products), 2000)
  },

  buyProducts(products, cb, errorCb) {
    setTimeout(() => {
      // simulate random checkout failure.
      (Math.random() > 0.5 || navigator.userAgent.indexOf('PhantomJS') > -1) ?
      cb(): errorCb()
    }, 100)
  }
}