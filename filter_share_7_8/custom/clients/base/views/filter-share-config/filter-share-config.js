({
    events: {
        'click [name=cancel_button]': 'cancelClicked',
        'click [name=save_button]': 'saveClicked',
        'click .record-panel-header': 'togglePanel',
        'change input.group-control': 'massUpdate',
        'change input.single-control': 'singleUpdate'
    },

    initialize: function (options) {
        this._super('initialize', [options]);

        var view = this;
        this.user_information = {};
        this.filterId = this.context.get('filterId');
        this.filterName = this.context.get('filterName');
        app.api.call('read', app.api.buildURL('getUsersForFilterShare'), null, {
            success: function (response) {
                view.user_information = response;
                view.render();
            }
        });
    },

    cancelClicked: function () {
        app.drawer.close(this.context);
    },

    saveClicked: function () {
        var users = [];

        var selectUsers = $(this.$el).find("input.single-control:checked");

        _.each(selectUsers, function (selectUser) {
            var value = selectUser.value
            if (_.indexOf(users, value) == -1) {
                users.push(value);
            }
        });

        if (users.length == 0) {
            app.alert.show('missing_user',
                {
                    level: 'error',
                    messages: 'Please select a user!'
                }
            );
            return false;
        }
        app.alert.dismiss('missing_user');
        var params = {
            user_ids: users,
            filter_id: this.filterId
        }

        app.alert.show('sharing_process',{
            level: 'process',
            title: 'Please wait...',
            autoClose: false
        });

        var view = this;
        app.api.call('create', app.api.buildURL('shareFilterWithUsers'), params, {
            success: function (response) {
                app.alert.dismiss('sharing_process');

                app.alert.show('sharing_done',{
                    level: 'success',
                    title: 'Your Filter was succesfully shared',
                    autoClose: true
                });

                app.drawer.close(view.context);
            }
        });
    },

    togglePanel: function (e) {
        var $panelHeader = this.$(e.currentTarget);
        if ($panelHeader && $panelHeader.next()) {
            $panelHeader.next().toggle();
            $panelHeader.toggleClass('panel-inactive panel-active');
        }
        if ($panelHeader && $panelHeader.find('i')) {
            $panelHeader.find('i').toggleClass('fa-chevron-up fa-chevron-down');
        }
    },

    massUpdate: function (event) {
        var view = this;
        var checked = event.target.checked;

        var inputs = $(event.target).next().next().find("input[type=checkbox]");

        _.each(inputs, function (input) {
            var sameInputs = $(view.$el).find("input[value=" + input.value + "]");
            _.each(sameInputs, function (sameInput) {
                sameInput.checked = checked;
            });
        });
    },

    singleUpdate: function (event) {
        var checked = event.target.checked;

        var sameInputs = $(this.$el).find("input[value=" + event.target.value + "]");
        _.each(sameInputs, function (sameInput) {
            sameInput.checked = checked;
        });
    }
})