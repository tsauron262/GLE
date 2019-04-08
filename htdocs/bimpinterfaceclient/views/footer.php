
<?php global $mysoc; ?>
    </div>
    </div>
    <script>
        $('.passwd').on('click', function () {
            $('#passwd').slideDown();
            $('.close-passwd').slideDown();
        });
        $('.close-passwd').on('click', function () {
            $('#passwd').slideUp();
            $('.close-passwd').slideUp();
        });
    </script>
    <footer class="footer">
        <div class="container-fluid">
            <nav class="pull-left">
                <ul>
                    <li>
                        <a href="#">
                            <?php
                            print($mysoc->address . ", " . $mysoc->zip . " " . $mysoc->town);
                            ?>
                        </a>
                    </li>

                </ul>
            </nav>
            <p class="copyright pull-right">
                BIMP-ERP
            </p>
        </div>
    </footer>

    </div>
    </div>
    </body>
    <script src="views/js/light-bootstrap-dashboard.js?v=1.4.0"></script>
    <script src="views/js/demo.js"></script>
    </html>