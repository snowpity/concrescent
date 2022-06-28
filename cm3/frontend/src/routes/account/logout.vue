<template>
<v-layout fluid
          fill-height
          align-center
          justify-center>
    <v-card max-width="600">
        <v-card-text class="text-h4">Logged out!</v-card-text>
        <v-card-actions>
            <v-spacer />
            <v-btn color="green darken-1"
                   :to="returnTo">OK</v-btn>
        </v-card-actions>
    </v-card>
</v-layout>
</template>

<script>
import {
    mapActions
} from 'vuex';
export default {
    //Taken from https://stackoverflow.com/a/64751397
    watch: {
        $route() {
            this.$nextTick(this.routeLoaded);
        }
    },
    data() {
        return {};
    },
    computed: {
        returnTo: function() {
            const currentRoute = this.$router.currentRoute;
            if (currentRoute.query != undefined && currentRoute.query.returnTo != undefined)
                return currentRoute.query.returnTo;
            if (currentRoute.params != undefined && currentRoute.params.returnTo != undefined)
                return currentRoute.params.returnTo;

            return '/';
        }
    },
    methods: {
        routeLoaded() {
            //Dom for the current route is loaded
            this.logout();
        },
        ...mapActions('mydata', [
            'logout',
        ]),
    },
    mounted() {
        /* The route will not be ready in the mounted hook if it's component is async
        so we use $router.onReady to make sure it is.
        it will fire right away if the router was already loaded, so catches all the cases.
        Just understand that the watcher will also trigger in the case of an async component on mount
        because the $route will change and the function will be called twice in this case,
        it can easily be worked around with a local variable if necessary
        */
        this.$router.onReady(() => this.routeLoaded());
    },
};
</script>
