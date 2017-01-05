({
    extendsFrom: "FilterActionsView",

    initialize: function (opts) {
        this._super("initialize", [opts]);

        this.events = _.extend(this.events, {'click a.share_button:not(.disabled)': 'triggerShare'})
        this.layout.on('filter:create:open', function (filterModel) {
            var showState = false;
            if (this.context.editingFilter.id) {
                showState = true
            }

            this.$('.share_button').toggleClass('disabled', !showState);
        }, this);
    },

    triggerShare: function() {
        app.drawer.open({
            layout:'filter-share-config',
            context:{
                create: true,
                filterId: this.context.editingFilter.id,
                filterName: this.context.editingFilter.get('name')
            }
        });
    }
})