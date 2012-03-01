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

/**
 * Toggle the state of the question asking form
 * @param state True to enable, false to disable
 *
 */
function toggleAsk(state) {
    var elements =  $('#form-ask input, #form-ask textarea, #form-ask select, #form-ask button');
    elements.attr('disabled', !state)

    // when enabling form, clear text
    if (state)
        elements.val('');
}

/**
 * Show a notification for and update the cell of an event
 * @param id ID of affected question
 * @param message Message to display
 * @param evaluate True to display feedback link
 *
 */
function updateEvent(id, message, evaluate) {
    // update UI
    var d = new Date;
    var html = message + ' on ' + d.toString('M/d/yy') + ' at ' + 
            d.toString('h:mmtt').replace(/^0:(.*)$/, '12:$1').toLowerCase() + '.';

    // display link to evaluation
    if (evaluate)
        html += ' <a href="#" class="a-evaluate">Evaluate your experience</a>';

    // update cell for given question
    $('tr[data-question-id="' + id + '"] .history-event').html(html);
}

$(function() {
    // connect to live server and subscribe to this suite
    var socket = io.connect('http://192.168.56.50:3000/questions/live');
    socket.on('connect', function() {
        socket.emit('subscribe', { subscription: suite_id });
    });

    socket.on('toHelp', function(data) {
        if (window.question_id == data.id) {
            updateEvent(data.id, 'posted to Help');
            showNotification('Your question has been posted to Help!');
        }
    });

    socket.on('toQueue', function(data) {
        if (window.question_id == data.id) {
            updateEvent(data.id, 'entered the Queue');
            showNotification('Your question has entered the Queue!');
        }
    });

    socket.on('dispatch', function(data) {
        // make sure we try to iterate over an array
        var ids = data.ids.split(',');
        if (!$.isArray(ids))
            ids = [ids];

        // check if this question has been dispatched
        for (var i in ids) {
            if (window.question_id == ids[i]) {
                // get info about the staff member the question has been dispatched to
                $.getJSON('/questions/get/' + ids[i], function(response) {
                    // update UI
                    updateEvent(ids[i], 'dispatched to ' + response.question.staff.name);

                    // show native alert to give tab focus
                    $(window).focus();
                    alert(response.question.staff.name + ' is ready to answer your question!');
                    showNotification(response.question.staff.name + ' is ready to answer your question!');

                    // in 3 minutes, re-enable form and change cell to "answered"
                    setTimeout(function() {
                        toggleAsk(true);
                        updateEvent(ids[i], 'answered by ' + response.question.staff.name, true);
                    }, 1800000);
                });
            }
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
            labels: [$('#select-label').val()],
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
            toggleAsk(false);

            // remember question id
            window.question_id = response.id;

            // if load balancer is diabled, then question has entered the queue immediately
            if (response.destination == 1)
                updateEvent(data.id, 'entered the Queue');
        });

        e.preventDefault();
        return false;
    });
});
