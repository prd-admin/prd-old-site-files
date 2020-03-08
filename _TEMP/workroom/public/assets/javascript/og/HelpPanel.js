og.HelpPanel = function(config) {
	og.HelpPanel.superclass.constructor.call(this, Ext.apply(config, {
		defaultContent: {
			type: 'url',
			data: 'public/help/index.html'
		},
		active: true
	}));
	this.load(this.defaultContent);
};

Ext.extend(og.HelpPanel, og.ContentPanel, {
	workspaceChanged: function() {
	}
});