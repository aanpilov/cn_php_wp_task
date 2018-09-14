jQuery(function ($) {

  /* DataTables init */
  if ($('#tasks_table').length) {
    ($('#tasks_table').DataTable());
  }

  /* Menu item modal init */
  $("a[href='#addTask']")
    .attr('data-toggle', 'modal')
    .attr('data-target', '.modal-add-task');

  /* Add task button */

  var cntask = {
    $modal: $('.modal-add-task'),
    task: {
      title: "",
      freelancer_id: 0
    },
    init: function () {
      this.bindings();
    },
    bindings: function () {
      this.$modal.on('click', '.js-add-task', function (e) {
        cntask.dataSubmit();
        e.preventDefault();
        e.stopPropagation();
      });
    },
    say: function (msg, type = "success") {
      $('p.message').removeClass('alert-success alert-danger');
      $('p.message').text(msg).addClass('alert-' + type);
    },
    clear: function () {
      $('p.message').removeClass('alert-success alert-danger');
      $('p.message').text('');
    },
    isDataValid: function () {
      this.task.title = this.task.title.trim();
      this.task.freelancer_id = this.task.freelancer_id * 1;
      if ('' == this.task.title) {
        this.say("Please enter task name", 'danger');
        return false;
      }
      return true;
    },
    dataSubmit: function () {
      this.clear();
      this.task.title = $('#task-title').val();
      this.task.freelancer_id = $('#freelancer_id').val();
      if (this.isDataValid()) {
        $.ajax({
          url: "/wp-admin/admin-ajax.php",
          type: "post",
          data: {
            'action': 'cn_add_task',
            'title': cntask.task.title,
            'freelancer_id': cntask.task.freelancer_id,
            'nonce': 'nonce'
          },
          dataType: 'json'
        }).done(function (data) {
          if ('undefined' !== data.status) {
            cntask.say(
              data.message,
              data.status
            );
            if ('success' == data.status) {
              setTimeout(function () {
                location.reload();
              }, 2000);
            }
          }
        });
      }
    },
    reloadPage: function () {

    }
  }

  if ($('.modal-add-task').length) {
    cntask.init();
  }

});
