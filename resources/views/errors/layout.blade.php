

<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> 
    <!-- Bootstrap CSS -->

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
    <link href="{{static_asset('backend')}}/vendor/fonts/circular-std/style.css" rel="stylesheet">
    <link rel="stylesheet" href="{{static_asset('backend')}}/libs/css/style.css">

    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/fonts/fontawesome/css/fontawesome-all.css">
</head>

<body class="bg-light">
   <!-- ============================================================== -->
    <!-- main wrapper -->
    <!-- ============================================================== -->
    <div class="dashboard-main-wrapper p-0">
        <!-- ============================================================== -->
        <!-- navbar -->
        <!-- ============================================================== -->
        <nav class="navbar navbar-expand dashboard-top-header bg-white">
            <div class="container-fluid">
                <!-- ============================================================== -->
                <!-- brand logo -->
                <!-- ============================================================== -->
                <div class="dashboard-nav-brand">
                </div>
                <!-- ============================================================== -->
                <!-- end brand logo -->
                <!-- ============================================================== -->
            </div>
        </nav>
        <!-- ============================================================== -->
        <!-- end navbar -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- wrapper  -->
        <!-- ============================================================== -->
        <div class="bg-light text-center">
            <div class="container">
                <div class="row">
                    <div class="offset-xl-2 col-xl-8 offset-lg-2 col-lg-8 col-md-12 col-sm-12 col-12">
                        <div class="error-section">
                            <div class="error-section-content">
                                <h1 class="display-3 text-secondary" style="font-size: 10em">@yield('code')</h1>
                                <h1 class="text-secondary">@yield('message-headline')</h1>
                                <h2 class="">@yield('message-title')</h2>
                                <p> @yield('message')</p>
                                <a href="{{ url('/') }}" class="btn btn-secondary btn-lg">Back to homepage</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- footer -->
        <!-- ============================================================== -->
        <div class="footer fixed-bottom">
            <div class="container-fluid">
                <div class="row">
                    <div class=" col-12">
                            Copyright © 2022 Concept. All rights reserved. Development by <a href="https://wemaxit.com">Wemax IT</a>.
                    </div>

                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- end footer -->
        <!-- ============================================================== -->
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- end main wrapper -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- end main wrapper -->
    <!-- ============================================================== -->
    <!-- Optional JavaScript -->
    <script src="../assets/vendor/jquery/jquery-3.3.1.min.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../assets/vendor/slimscroll/jquery.slimscroll.js"></script>
    <script src="../assets/libs/js/main-js.js"></script>
</body>

</html>
