<!DOCTYPE html>
<html>
<head>
    <title>Debug - Available IDs</title>
    <script src="assets/libs/jquery/jquery.min.js"></script>
</head>
<body>
    <h1>Available IDs in Database</h1>

    <h2>Brands</h2>
    <div id="brands"></div>

    <h2>Groups</h2>
    <div id="groups"></div>

    <h2>Categories</h2>
    <div id="categories"></div>

    <h2>Stock Types</h2>
    <div id="stock_types"></div>

    <script>
        $(document).ready(function() {
            // Load brands
            $.post('ajax/php/item-master.php', { action: 'debug_brands' }, function(response) {
                if (response.status === 'success') {
                    let html = '<table border="1"><tr><th>ID</th><th>Name</th></tr>';
                    response.brands.forEach(function(item) {
                        html += '<tr><td>' + item.id + '</td><td>' + item.name + '</td></tr>';
                    });
                    html += '</table>';
                    $('#brands').html(html);
                } else {
                    $('#brands').html('<p>Error: ' + response.message + '</p>');
                }
            });

            // Load groups
            $.post('ajax/php/item-master.php', { action: 'debug_groups' }, function(response) {
                if (response.status === 'success') {
                    let html = '<table border="1"><tr><th>ID</th><th>Name</th></tr>';
                    response.groups.forEach(function(item) {
                        html += '<tr><td>' + item.id + '</td><td>' + item.name + '</td></tr>';
                    });
                    html += '</table>';
                    $('#groups').html(html);
                } else {
                    $('#groups').html('<p>Error: ' + response.message + '</p>');
                }
            });

            // Load categories
            $.post('ajax/php/item-master.php', { action: 'debug_categories' }, function(response) {
                if (response.status === 'success') {
                    let html = '<table border="1"><tr><th>ID</th><th>Name</th></tr>';
                    response.categories.forEach(function(item) {
                        html += '<tr><td>' + item.id + '</td><td>' + item.name + '</td></tr>';
                    });
                    html += '</table>';
                    $('#categories').html(html);
                } else {
                    $('#categories').html('<p>Error: ' + response.message + '</p>');
                }
            });

            // Load stock types
            $.post('ajax/php/item-master.php', { action: 'debug_stock_types' }, function(response) {
                if (response.status === 'success') {
                    let html = '<table border="1"><tr><th>ID</th><th>Name</th></tr>';
                    response.stock_types.forEach(function(item) {
                        html += '<tr><td>' + item.id + '</td><td>' + item.name + '</td></tr>';
                    });
                    html += '</table>';
                    $('#stock_types').html(html);
                } else {
                    $('#stock_types').html('<p>Error: ' + response.message + '</p>');
                }
            });
        });
    </script>
</body>
</html>
