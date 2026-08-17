<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME; ?> | Enterprise Human Resource System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <link href="assets/css/style.css?v=<?= time(); ?>" rel="stylesheet">
    <style>
    /* Curated Light Purple Theme Force Overrides */
    :root {
        --primary: #7C3AED !important;
        --primary-hover: #6D28D9 !important;
        --secondary: #8B5CF6 !important;
        --background: #F4F0FA !important;
        --surface: #FFFFFF !important;
        --border: #E9D8FD !important;
        --text: #1E1B4B !important;
        --text-light: #4A5568 !important;
        --text-muted: #718096 !important;
        --hover: #F3E8FF !important;
    }

    body {
        background-color: #F4F0FA !important;
        color: #1E1B4B !important;
    }

    #sidebar {
        background: #FFFFFF !important;
        border-right: 1px solid #E9D8FD !important;
    }

    #sidebar .nav-link {
        color: #4A5568 !important;
    }

    #sidebar .nav-link:hover {
        background-color: #F3E8FF !important;
        color: #7C3AED !important;
    }

    #sidebar .nav-link.active {
        color: #7C3AED !important;
        background-color: #EDE9FE !important;
        font-weight: 600 !important;
    }

    #sidebar .nav-link.active i {
        color: #7C3AED !important;
    }

    #sidebar .nav-link.active::before {
        content: '' !important;
        position: absolute !important;
        left: -12px !important;
        top: 4px !important;
        bottom: 4px !important;
        width: 4px !important;
        border-radius: 0 4px 4px 0 !important;
        background-color: #7C3AED !important;
    }

    #navbar {
        background-color: #F4F0FA !important;
    }
    </style>
</head>
<body>
<div id="app">
