<template>
<v-app id="app"
       :style="{ background: $vuetify.theme.themes.light.backgroundcolor}">
    <v-navigation-drawer v-model="drawer"
                         app
                         temporary>
        <v-list>
            <v-list-item>
                <v-select v-model="productselectedEventId"
                          label="Selected event"
                          :items="events"
                          item-text="display_name"
                          item-value="id">
                </v-select>
            </v-list-item>
        </v-list>
        <v-list dense>
            <div v-for="menuitem in drawerItems"
                 :key="menuitem.route">
                <v-divider v-if="menuitem.divider" />
                <v-list-item :to="menuitem.route"
                             v-show="menuitem.show == null || menuitem.show()"
                             v-else>
                    <v-list-item-action>
                        <v-badge :value="menuitem.badge != null && menuitem.badge() != null">
                            <template v-slot:badge>
                                {{menuitem.badge == null ? null : menuitem.badge()}}
                            </template>
                            <v-icon>{{menuitem.icon}}</v-icon>
                        </v-badge>
                    </v-list-item-action>
                    <v-list-item-content>
                        <v-list-item-title>{{menuitem.label}}</v-list-item-title>
                    </v-list-item-content>
                </v-list-item>
            </div>
        </v-list>
        <v-spacer></v-spacer>
    </v-navigation-drawer>

    <v-app-bar app
               color="appbar"
               dark>
        <v-app-bar-nav-icon @click.stop="drawer = !drawer"></v-app-bar-nav-icon>
        <v-toolbar-title>{{appHead}}</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-menu bottom
                left>
            <template v-slot:activator="{ on, attrs }">
                <v-btn icon
                       v-bind="attrs"
                       v-on="on">
                    <v-icon>mdi-{{profileIcon}}</v-icon>
                </v-btn>
            </template>

            <v-list>
                <v-list-item>
                    <v-list-item-content>
                        <v-list-item-title>{{getLoggedInName}}</v-list-item-title>
                    </v-list-item-content>
                    <v-list-item-action>
                        <v-icon>mdi-{{profileIcon}}</v-icon>
                    </v-list-item-action>
                </v-list-item>
                <v-divider />
                <v-list-item v-for="(menuitem, i) in profileItems"
                             :key="i"
                             :to="menuitem.route"
                             v-show="menuitem.show == null || menuitem.show()">
                    <v-divider v-if="menuitem.divider" />
                    <v-list-item-action v-else>
                        <v-badge :value="menuitem.badge != null && menuitem.badge() != null">
                            <template v-slot:badge>
                                {{menuitem.badge == null ? null : menuitem.badge()}}
                            </template>
                            <v-icon>{{menuitem.icon}}</v-icon>
                        </v-badge>
                    </v-list-item-action>
                    <v-list-item-content>
                        <v-list-item-title>{{menuitem.label}}</v-list-item-title>
                    </v-list-item-content>
                </v-list-item>
                <div v-if="isAdmin">
                    <v-divider />
                    <v-list-item>
                        <v-list-item-content>
                            <v-list-item-title>Switch to Admin</v-list-item-title>
                        </v-list-item-content>
                        <v-list-item-action>
                            <v-switch v-model="adminMode" />
                        </v-list-item-action>
                    </v-list-item>
                </div>
            </v-list>
        </v-menu>

        <template v-slot:extension
                  v-if="hasSubTabs">
            <v-tabs v-model="subTabIx"
                    align-with-title>
                <v-tabs-slider color="yellow"></v-tabs-slider>

                <v-tab v-for="item in subTabs"
                       :key="item.key">
                    {{ item.text }}
                </v-tab>
            </v-tabs>
        </template>
    </v-app-bar>
    <v-main>
        <router-view :subTabIx="hasSubTabs ? subTabs[subTabIx].key : 0"
                     @updateSubTabs="updateSubTabs"
                     @updateSubTitle="updateSubTitle" />
    </v-main>
</v-app>
</template>


<script>
const config = require("../customization/config.js");
import {
    mapGetters
} from 'vuex'
export default {
    data: () => ({
        drawer: false,
        subHead: null,
        subTabs: [],
        subTabIx: 0
    }),
    computed: {
        appTitle: function() {
            var result = [this.AppName];
            if (this.subHead != null) {
                result.push(this.subHead);
            } else
            if (this.$route.name != null) {
                result.push(this.$route.meta.title || this.$route.name);
            }
            if (this.hasSubTabs) {
                var subTabInfo = this.subTabs[this.subTabIx];
                if (subTabInfo != undefined) {
                    //Determine if the subtab has a title
                    if (subTabInfo.title) {
                        result.push("" + subTabInfo.title);
                    }
                }
            }

            return result.join(' - ');
        },
        appHead: function() {
            var result = [this.AppName];
            if (this.subHead != null) {
                result.push(this.subHead);
            } else
            if (this.$route.name != null) {
                result.push(this.$route.meta.title || this.$route.name);
            }

            return result.join(' - ');
        },
        hasSubTabs: function() {
            return this.subTabs.length > 0;
        },
        drawerItems: function() {
            var items = [{
                route: "/",
                icon: "mdi-home",
                label: "Home"
            }];
            if (!this.adminMode) {
                items.push({
                    route: "/myBadges",
                    icon: "mdi-badge-account-horizontal",
                    label: "My badges",
                    show: () => {
                        return this.ownedBadgeCount > 0;
                    }
                }, {
                    route: "/myApplications",
                    icon: "mdi-account-box-multiple",
                    label: "My Applications",
                    show: () => {
                        return this.applicationCount > 0;
                    }
                }, {
                    route: "/addbadge",
                    icon: "mdi-cart-plus",
                    label: "Add Badge"
                }, {
                    route: "/cart",
                    icon: "mdi-cart",
                    label: "View cart",
                    badge: () => {
                        return this.cartCount > 0 ? this.cartCount : null;
                    }
                });
            } else {
                //Some general event permissions

                items.push({
                    route: "/Admin/Badge_Checkin",
                    icon: "mdi-qrcode-scan",
                    label: "Badge Check-in",
                    show: () => {
                        return this.hasEventPerm('Badge_Checkin');
                    }
                }, {
                    route: "/Admin/Locations",
                    icon: "mdi-map",
                    label: "Event Locations",
                    show: () => {
                        return this.hasEventPerm('Location_Manage');
                    }
                }, {
                    divider: true
                }, {
                    route: "/Admin/Attendee",
                    icon: "mdi-badge-account-horizontal",
                    label: "Attendees",
                    show: () => {
                        return this.hasEventPerm('Attendee_View');
                    }
                }, );

                //The groups
                this.badgeContexts.forEach((group, i) => {
                    if (group.id > 0) {
                        items.push({
                            route: "/Admin/Application/" + group.context_code,
                            icon: "mdi-" + (group.menu_icon != null ? group.menu_icon : "newspaper"),
                            label: group.name,
                            show: () => {
                                return this.hasGroupPerm(group.id, 'Submission_View');
                            }

                        })
                    }
                });

                //Staff and management

                items.push({
                    divider: true
                }, {
                    route: "/Admin/Staff",
                    icon: "mdi-account-hard-hat",
                    label: "Staff",
                    show: () => {
                        return this.hasEventPerm('Staff_View');
                    }
                }, {
                    route: "/Admin/OrgChart",
                    icon: "mdi-domain",
                    label: "Org Chart",
                    show: () => {
                        return true; //return this.hasEventPerm('Badge_Stats');
                    }
                }, {
                    route: "/Admin/Badge_Stats",
                    icon: "mdi-chart-bell-curve-cumulative",
                    label: "Badge Stats",
                    show: () => {
                        return this.hasEventPerm('Badge_Stats');
                    }
                }, {
                    route: "/Admin/Users",
                    icon: "mdi-badge-account",
                    label: "Admin Users",
                    show: () => {
                        return this.hasEventPerm('Manage_Users');
                    }
                }, {
                    route: "/Admin/Printing",
                    icon: "mdi-printer",
                    label: "Badge Printing",
                    show: () => {
                        return this.hasEventPerm('Badge_Print');
                    }
                }, {
                    route: "/Admin/Payments",
                    icon: "mdi-credit-card",
                    label: "Payments",
                    show: () => {
                        return this.hasEventPerm('Payment_View');
                    }
                }, );

                //System

                items.push({
                    divider: true,
                    show: () => {
                        return this.hasEventPerm(['GlobalAdmin', 'EventAdmin']);
                    }
                }, {
                    route: "/Admin/System",
                    icon: "mdi-cog",
                    label: "System Setup",
                    show: () => {
                        return this.hasEventPerm(['GlobalAdmin', 'EventAdmin']);
                    }
                }, );
            }


            return items;
        },
        profileItems: function() {
            var items = [{
                route: "/login",
                icon: "mdi-login",
                label: "Login",
                show: () => {
                    return !this.isLoggedIn;
                }
            }, {
                route: "/account/profile",
                icon: "mdi-account-plus",
                label: "Create Account",
                show: () => {
                    return !this.isLoggedIn;
                }
            }, {
                route: "/account/profile",
                icon: "mdi-account-box",
                label: "Account Profile",
                show: () => {
                    return this.isLoggedIn;
                }
            }, {
                route: "/account/logout",
                icon: "mdi-logout",
                label: "Logout",
                show: () => {
                    return this.isLoggedIn;
                }
            }, {
                route: "/account/history",
                icon: "mdi-shopping-search",
                label: "Purchase History",
                show: () => {
                    return false;
                    //Not ready yet
                    //return this.isLoggedIn;
                }
            }, {
                route: "/account/settings",
                icon: "mdi-cog",
                label: "Preferences",
                show: () => {
                    return this.adminMode;
                }
            }];

            return items;
        },
        profileIcon: function() {
            if (this.isLoggedIn) {
                if (this.isAdmin) {
                    return 'badge-account';
                } else {
                    return 'account';
                }
            }
            return 'account-alert-outline';
        },
        ...mapGetters('cart', {
            'cartCount': 'cartCount'
        }),
        ...mapGetters('mydata', {
            'ownedBadgeCount': 'ownedBadgeCount',
            'applicationCount': 'applicationCount',
            'isLoggedIn': 'getIsLoggedIn',
            'isAdmin': 'hasPerms',
            'hasEventPerm': 'hasEventPerm',
            'hasGroupPerm': 'hasGroupPerm',
            'getLoggedInName': 'getLoggedInName'
        }),
        ...mapGetters('products', {
            'events': 'events',
            'badgeContexts': 'badgeContexts',
            'productselectedEvent': 'selectedEvent'
        }),
        productselectedEventId: {
            get: function() {
                return this.$store.getters['products/selectedEventId'];
            },
            set: function(event_id) {
                console.log('Switching event to ' + event_id);
                this.$store.dispatch("products/selectEventId", event_id);
                //TODO: This should trigger a reload of everything!
                if (this.isLoggedIn) {
                    this.$store.dispatch("mydata/RefreshToken");
                }
            }
        },
        adminMode: {
            get: function() {
                return this.$store.getters['mydata/getAdminMode'];
            },
            set: function(newAdminMode) {
                this.$store.dispatch("mydata/setAdminMode", newAdminMode);
                //Bring us back to the home page if we're not there already
                if (this.$router.currentRoute.name != 'home')
                    this.$router.push("/");
            }
        },
        AppName: function() {
            return this.adminMode ? config.AppNameAdmin : config.AppName;
        },
        eventDates: function() {
            return this.productselectedEvent.date_start + "-" + this.productselectedEvent.date_end;
        }
    },
    methods: {
        updateSubTitle(newSubTitle) {
            this.subHead = newSubTitle;
        },
        updateSubTabs(newSubTabs) {
            this.subTabs = newSubTabs;
            //TODO:Determine if the current route is intended to be on a particular sub-tab


        }
    },
    watch: {
        '$route.name': function(name) {
            console.log("Switching route to " + name);
            //Reset title
            document.title = this.appTitle;
            //Expect a new set of subtabs if this route has them
            this.subTabs = [];
            this.subTabIx = 0;
            this.subHead = null;
        },
        'appTitle': function(newTitle) {
            document.title = this.appTitle;
        },
        'subTabIx': function(newSubTab) {
            console.log('Switching subtab to ' + newSubTab, this.subTabs[this.subTabIx].key);
        }
    },
    created() {
        document.title = this.appTitle;

        this.$store.dispatch('products/getEventInfo').then(() => {
            if (this.isLoggedIn) {
                console.log('refreshing token, event id ' + this.productselectedEventId)
                this.$store.dispatch('mydata/RefreshToken');
            }
            console.log('got event info, loading contexts');
            //this.selectedEventId = this.productselectedEventId;
            this.$store.dispatch('products/getBadgeContexts');
        });
    }
};
</script>
