<?php
switch ('__default__'):
default:
                if (!canAccess($page) && $page != 'dashboard') {
                    echo '<div class="alert alert-danger" style="margin:2rem;text-align:center;"><div style="font-size:4rem;margin-bottom:1rem;">⛔</div><h2>Access Denied</h2><p>You do not have permission to access this page.</p><a href="?page=dashboard" class="btn btn-primary" style="margin-top:1rem;">Go to Dashboard</a></div>';
                } else {
                    header('Location: ?page=dashboard');
                    exit();
                }
break;
endswitch;
