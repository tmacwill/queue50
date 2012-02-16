var template_html = {
    history_row: '\
        <tr data-question-id="<%= question_id %>"> \
            <td> \
                <h4 class="history-question"><%= question %></h4> \
                <div class="history-event"> \
                    <%= history %> \
                </div> \
            </td> \
        </tr> \
    '
};

// compile templates
window.templates = {};
for (var i in window.template_html)
    window.templates[i] = _.template(template_html[i]);

/**
 * Show an auto-hiding notification to update user on question status
 * @param message Message to show to user
 *
 */
function showNotification(message) {
    if (window.webkitNotifications) {
        // create notification based on message
        var notification = window.webkitNotifications
            .createNotification('', 'CS50 Queue', message);

        // auto-hide notification after 5 seconds
        setTimeout(function() {
            notification.cancel();
        }, 5000);

        notification.show();
    }
}

$(function() {
    // connect to live server and subscribe to this suite
    var socket = io.connect('http://192.168.56.50:3000/questions/live');
    socket.on('connect', function() {
        socket.emit('subscribe', { subscription: suite_id });
    });

    socket.on('toHelp', function(data) {
        if (window.question_id == data.id) {
            // update UI
            var d = new Date;
            showNotification('Your question has been posted to Help!');
            $('tr[data-question-id="' + data.id + '"] .history-event')
                .html('posted to Help on ' + d.toString('M/d/yy') + ' at ' + 
                    d.toString('h:mmtt').replace(/^0:(.*)$/, '12:$1').toLowerCase());

            // enable form
            $('#form-ask input, #form-ask textarea, #form-ask select').attr('disabled', false).val('');
        }
    });

    socket.on('toQueue', function(data) {
        if (window.question_id == data.id) {
            // update UI
            var d = new Date;
            showNotification('Your question has entered the queue!');
            $('tr[data-question-id="' + data.id + '"] .history-event')
                .html('entered the queue on ' + d.toString('M/d/yy') + ' at ' + 
                    d.toString('h:mmtt').replace(/^0:(.*)$/, '12:$1').toLowerCase());

            // enable form
            $('#form-ask input, #form-ask textarea, #form-ask select').attr('disabled', false).val('');
        }
    });

    // ask button sends question to server
    $('#btn-ask').on('click', function(e) {
        // request notification permission
        if (window.webkitNotifications) {
            if (window.webkitNotifications.checkPermission() != 0) {
                window.webkitNotifications.requestPermission();
            }
        }

        // construct post data
        var question = {
            label: $('#select-label').val(),
            question: $('#txt-question').val(),
            title: $('#txt-title').val()
        };
        var d = new Date;

        // send request to api route for new question
        $.post('/questions/add/' + suite_id, question, function(response) {
            response = JSON.parse(response);

            // add question to right panel
            $('#table-history tbody').prepend(templates.history_row({ 
                history: 'asked on ' + d.toString('M/d/yy') + ' at ' + d.toString('h:mmtt').replace(/^0:(.*)$/, '12:$1').toLowerCase(),
                question: question.title,
                question_id: response.id
            }));
            $('#table-history tbody tr:first-child td').effect('highlight', {}, 1000);

            // disable form
            $('#form-ask input, #form-ask textarea, #form-ask select').attr('disabled', true);

            // remember question id
            window.question_id = response.id;
        });

        e.preventDefault();
        return false;
    });
});
