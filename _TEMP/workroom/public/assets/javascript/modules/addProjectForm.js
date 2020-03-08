App.modules.addProjectForm = {
  formActionClick: function() {
    Ext.getDom('projectFormActionSelectMessage').disabled = !Ext.getDom('projectFormActionAddComment').checked;
    Ext.getDom('projectFormActionSelectTaskList').disabled = !Ext.getDom('projectFormActionAddTask').checked;
  }
};