var express = require('express');
var app = express.createServer();
var io = require('socket.io').listen(app);

app.configure(function() {
    app.use(express.bodyParser());
    app.use(express.methodOverride());
});

app.configure('development', function() {
    app.use(express.errorHandler({ dumpExceptions: true, showStack: true }));
});

app.configure('production', function() {
    app.use(express.errorHandler());
});

// question has been added
app.get('/questions/add/:suite_id', function(req, res) {
    io.of('/questions/live').in(req.params.suite_id).emit('add');
    res.writeHead(200, {'Content-Type': 'text/plain'});
    res.end('');
});

// question has been dispatched
app.get('/questions/dispatch/:suite_id/:ids', function(req, res) {
    io.of('/questions/live').in(req.params.suite_id).emit('dispatch', { ids: req.params.ids });
    res.writeHead(200, {'Content-Type': 'text/plain'});
    res.end('');
});

// question has been sent to help
app.get('/questions/toHelp/:suite_id/:id', function(req, res) {
    io.of('/questions/live').in(req.params.suite_id).emit('toHelp', { id: req.params.id });
    res.writeHead(200, {'Content-Type': 'text/plain'});
    res.end('');
});

// question has been sent to queue
app.get('/questions/toQueue/:suite_id/:id', function(req, res) {
    io.of('/questions/live').in(req.params.suite_id).emit('toQueue', { id: req.params.id });
    res.writeHead(200, {'Content-Type': 'text/plain'});
    res.end('');
});

var live = io.of('/questions/live').on('connection', function(socket) {
    // subscribe to updates for the given suite
    socket.on('subscribe', function(data) {
        socket.set('subscription', data.subscription);
        socket.join(data.subscription);
    });
});

app.listen(3000);
console.log("Express server listening on port %d in %s mode", app.address().port, app.settings.env);
