<script>
var course_id = <?= $course_id ?>;
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
                <button id="btn-ask" class="btn btn-primary">Ask!</button>
            </form>
        </div>
        <div class="span6">
            <h1 class="page-header">Question History</h1>
            <table id="table-history" class="table table-striped">
                <?php foreach ($questions as $question): ?>
                    <tr>
                        <td data-question-id="<?= $question->id ?>">
                            <h4 class="history-question"><?= $question->title ?></h4>
                            <div class="history-staff">
                                <?php if ($question->answered == 0): ?>
                                    asked on <?= date('n/j/y', strtotime($question->ask_timestamp)) ?> at 
                                    <?= strtolower(date('g:iA', strtotime($question->ask_timestamp))) ?>
                                <?php else: ?>
                                    answered by <strong><?= $question->staff_id ?></strong> on 
                                    <?= date('n/j/y', strtotime($question->dispatch_timestamp)) ?> at 
                                    <?= strtolower(date('g:iA', strtotime($question->dispatch_timestamp))) ?>. 
                                    <a href="#">Evaluate your experience.</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <!--tr>
                    <td>
                        <h4 class="history-question">Why won't my program compile?</h4>
                        <div class="history-staff">
                            answered by <strong>Tommy MacWilliam</strong> on 12/12/12 at 12:00am. 
                            <a href="#">Evaluate your experience.</a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h4 class="history-question">Why won't my program compile?</h4>
                        <div class="history-staff">
                            answered by <strong>Tommy MacWilliam</strong> on 12/12/12 at 12:00am. 
                            <a href="#">Evaluate your experience.</a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h4 class="history-question">Why won't my program compile?</h4>
                        <div class="history-staff">
                            answered by <strong>Tommy MacWilliam</strong> on 12/12/12 at 12:00am. 
                            <a href="#">Evaluate your experience.</a>
                        </div>
                    </td>
                </tr-->
            </table>
        </div>
    </div>
</div>
