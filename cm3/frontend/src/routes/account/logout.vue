<template>
<div>
<h1>Logged out!</h1>
        <v-btn color="primary" >OK</v-btn>
</div>
</template>

<script>
import { mapActions } from 'vuex';
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
