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
    $('#btn-ask').on('click', function(e) {
        var question = {
            label: $('#select-label').val(),
            question: $('#txt-question').val(),
            title: $('#txt-title').val()
        };
        var d = new Date;

        $.post('/questions/add/' + course_id, question, function(response) {
            response = JSON.parse(response);
            $('#table-history tbody').prepend(templates.history_row({ 
                history: 'asked on ' + d.toString('M/d/yy') + ' at ' + d.toString('h:mmtt').toLowerCase(),
                question: question.title,
                question_id: response.id
            }));
            $('#table-history tbody tr:first-child td').effect('highlight', {}, 1000);
        });

        e.preventDefault();
        return false;
    });
});
