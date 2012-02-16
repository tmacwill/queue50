var template_html = {
    history_row: '\
        <tr data-question-id="<%= question_id %>"> \
            <td> \
                <h4 class="history-question"><%= question %></h4> \
                <div class="history-staff"> \
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

$(function() {
    // connect to live server and subscribe to this suite
    var socket = io.connect('http://192.168.56.50:3000/questions/live');
    socket.on('connect', function() {
        socket.emit('subscribe', { subscription: suite_id });
    });

    socket.on('toHelp', function(data) {
        if (window.question_id == data.id) {
            alert('Your question has been sent to help!');

            // enable form
            $('#form-ask input, #form-ask textarea, #form-ask select').attr('disabled', false).val('');
        }
    });

    socket.on('toQueue', function(data) {
        if (window.question_id == data.id) {
            alert('Your question has been sent to queue!');

            // enable form
            $('#form-ask input, #form-ask textarea, #form-ask select').attr('disabled', false).val('');
        }
    });

    // ask button sends question to server
    $('#btn-ask').on('click', function(e) {
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