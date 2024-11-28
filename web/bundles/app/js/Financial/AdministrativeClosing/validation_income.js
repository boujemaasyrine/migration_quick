$(function() {

    function importTickets() {
        loader.unblock();
        apiLoader.blockApiLoader();
        $.ajax({
            'url': Routing.generate('import_recent_tickets'),
            'success': function () {
                apiLoader.unblockApiLoader();
            }
        });
    }

    importTickets();
});