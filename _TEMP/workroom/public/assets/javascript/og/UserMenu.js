og.UserMenu = function(config, users) {
	if (!config) config = {};
	
	og.UserMenu.superclass.constructor.call(this, Ext.apply(config, {
		cls: 'scrollable-menu',
		items: [ 
			'-', {
			text: lang('view all'),
			handler: function() {
				this.fireEvent('userselect', -1);
			},
			scope: this,
			id: lang('view all')
		}]
	}));
	
	if (Ext.isIE) { // Add scrollbar in IE
		this.getEl().child('ul.x-menu-list').addClass('iemenulist');
		this.getEl().child('ul.x-menu-list').setWidth(this.getEl().child('ul.x-menu-list').getWidth()+20);
	}
	
	this.addEvents({userselect: true});
	this.userids = {};
	if (users) {
		this.addUsers(users);
	}
	
	this.loadUsers();
};

Ext.extend(og.UserMenu, Ext.menu.Menu, {

	addUser : function(user){
		var exists = this.userids[user.id];
		if (exists) {
			return;
		}
		var item = new Ext.menu.Item({
			text: og.clean(user.name),
			handler: function() {
				this.fireEvent('userselect', user.id);
			},
			scope: this
		});
		this.insert(0, item);
		this.userids[user.id] = item;
		return item;
	},
	
	exists: function(userid) {
		return this.userids[userid];
	},
	
	addUsers: function(users) {
		for (var i=0; i < users.length; i++) {
			this.addUser(users[i]);
		}
	},

	loadUsers: function() {
		og.openLink(og.getUrl('user', 'list_users'),{
			callback: function(success, data) {
				if (success) {
					try {
						var users = data.users;
						this.addUsers(users);
					} catch (e) {
						throw e;
					}
				}
			},
			scope: this
		});
	}
});
