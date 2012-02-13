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

// new question asked, so broadcast to all sockets in this suite
app.get('/questions/add/:suite_id', function(req, res) {
    io.of('/questions/live').in(req.params.suite_id).emit('new_question');
    res.writeHead(200, {'Content-Type': 'text/plain'});
    res.end('');
});

var live = io.of('/questions/live').on('connection', function(socket) {
    // subscribe to updates for the given class
    socket.on('subscribe', function(data) {
        socket.set('subscription', data.subscription);
        socket.join(data.subscription);
    });
});

app.listen(3000);
console.log("Express server listening on port %d in %s mode", app.address().port, app.settings.env);
