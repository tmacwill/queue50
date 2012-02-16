<script>
var suite_id = <?= $suite_id ?>;
</script>

<div class="container">
    <div class="row">
        <table id="load-balancer" class="table table-striped">
            <?php foreach ($questions as $question): ?>
                <tr data-question-id="<?= $question->id ?>">
                    <td>
                        <h4><?= $question->title ?></h4>
                        <div>
                            <?= $question->question ?><br />
                            asked on <?= date('n/j/y', strtotime($question->ask_timestamp)) ?> at 
                            <?= strtolower(date('g:iA', strtotime($question->ask_timestamp))) ?>
                        </div>
                    </td>
                    <td style="width: 100px; vertical-align: middle">
                        <button class="btn btn-primary btn-help">Post to Help</button>
                    </td>
                    <td style="width: 120px; vertical-align: middle">
                        <button class="btn btn-queue">Send to Queue</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
