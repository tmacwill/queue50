<script>
var suite_id = <?= $suite_id ?>;
</script>

<div class="container">
    <div class="row">
        <div class="span6">
            <h1 class="page-header">Ask a Question</h1>

            <form id="form-ask">
                <select id="select-label">
                    <option value="">Give your question a label.</option>
                    <option value="1">pset1</option>
                    <option value="2">pset2</option>
                </select>
                <br />
                <input id="txt-title" type="text" placeholder="What's your question in once sentence?" />
                <br />
                <textarea id="txt-question" placeholder="Elaborate on your question (if you need to!)"></textarea>
                <br />
                <button id="btn-ask" class="btn btn-primary btn-large">Ask!</button>
            </form>
        </div>
        <div class="span6">
            <h1 class="page-header">Question History</h1>
            <table id="table-history" class="table table-striped">
                <tbody>
                    <?php foreach ($questions as $question): ?>
                        <tr data-question-id="<?= $question->id ?>">
                            <td>
                                <h4 class="history-question"><?= $question->title ?></h4>
                                <div class="history-event">
                                    <?php if ($question->state == 0): ?>
                                        asked on <?= date('n/j/y', strtotime($question->ask_timestamp)) ?> at 
                                        <?= strtolower(date('g:iA', strtotime($question->ask_timestamp))) ?>
                                    <?php elseif ($question->state == 1): ?>
                                        entered the Queue on <?= date('n/j/y', strtotime($question->action_timestamp)) ?> at 
                                        <?= strtolower(date('g:iA', strtotime($question->action_timestamp))) ?>
                                    <?php elseif ($question->state == 2): ?>
                                        posted to Help on <?= date('n/j/y', strtotime($question->action_timestamp)) ?> at 
                                        <?= strtolower(date('g:iA', strtotime($question->action_timestamp))) ?>
                                    <?php elseif ($question->state == 3): ?>
                                        answered <?php if ($question->staff_id): ?> by <strong><?= $question->staff_id ?></strong><?php endif; ?> on 
                                        <?= date('n/j/y', strtotime($question->dispatch_timestamp)) ?> at 
                                        <?= strtolower(date('g:iA', strtotime($question->dispatch_timestamp))) ?>. 
                                        <a href="#">Evaluate your experience.</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
