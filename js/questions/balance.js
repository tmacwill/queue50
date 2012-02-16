var template_html = {
    row: '\
        <tr data-question-id="<%= question.id %>"> \
            <td> \
                <h4><%= question.title %></h4> \
                <div> \
                    <%= question.question %><br /> \
                    asked on <%= date %> at <%= time %> \
                </div> \
            </td> \
            <td style="width: 100px; vertical-align: middle"> \
                <button class="btn btn-primary btn-help">Post to Help</button> \
            </td> \
            <td style="width: 120px; vertical-align: middle"> \
                <button class="btn btn-queue">Send to Queue</button> \
            </td> \
        </tr> \
    '
};

// compile templates
window.templates = {};
for (var i in window.template_html)
    window.templates[i] = _.template(template_html[i]);

$(function() {
    // connect to live server and subscribe to this suite
    var socket = io.connect('http://192.168.56.50:3000/questions/live');
    socket.on('connect', function() {
        socket.emit('subscribe', { subscription: suite_id });
    });

    // question added, so refresh load balancer view
    socket.on('add', function() {
        $.getJSON('/questions/loadBalancer/' + suite_id, function(response) {

            var html = '';
            for (var i in response) {
                html += templates.row({
                    date: Date.parse(response[i].ask_timestamp).toString('M/d/yy'),
                    question: response[i],
                    time: Date.parse(response[i].ask_timestamp).toString('h:mmtt').replace(/^0:(.*)$/, '12:$1').toLowerCase()
                });
            }

            $('#load-balancer').html(html);
        });
    });

    // help button posts question to help.cs50
    $('#load-balancer').on('click', '.btn-help', function() {
        var tr = $(this).parents('tr');
        $.post('/questions/sendToHelp', { id: tr.data('question-id') }, function(response) {
            response = JSON.parse(response);

            if (response.success) {
                tr.fadeOut('fast', function() { tr.remove(); });
            }
        });
    });

    // queue button sends question to the queue
    $('#load-balancer').on('click', '.btn-queue', function() {
        var tr = $(this).parents('tr');
        $.post('/questions/sendToQueue', { id: tr.data('question-id') }, function(response) {
            response = JSON.parse(response);

            if (response.success) {
                tr.fadeOut('fast', function() { tr.remove(); });
            }
        });
    });
});
