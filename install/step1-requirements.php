<?php
// Check PHP version and extensions
$phpVersion = PHP_VERSION;
$requiredVersion = '7.4.0';
$phpOk = version_compare($phpVersion, $requiredVersion, '>=');

$extensions = [
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'mbstring' => extension_loaded('mbstring'),
    'openssl' => extension_loaded('openssl'),
    'curl' => extension_loaded('curl'),
    'gd' => extension_loaded('gd'),
    'fileinfo' => extension_loaded('fileinfo'),
    'zip' => extension_loaded('zip')
];

$allExtensionsOk = !in_array(false, $extensions);
$allOk = $phpOk && $allExtensionsOk;

// Check directory permissions
$directories = [
    ROOT_PATH . '/config' => is_writable(ROOT_PATH . '/config'),
    ROOT_PATH . '/uploads' => is_writable(ROOT_PATH . '/uploads'),
    ROOT_PATH . '/uploads/exams' => is_writable(ROOT_PATH . '/uploads/exams'),
    ROOT_PATH . '/uploads/profiles' => is_writable(ROOT_PATH . '/uploads/profiles'),
    ROOT_PATH . '/uploads/payments' => is_writable(ROOT_PATH . '/uploads/payments')
];

$allDirsOk = !in_array(false, $directories);
$allOk = $allOk && $allDirsOk;
?>

<div class="text-center mb-4">
    <h3>System Requirements Check</h3>
    <p class="text-muted">Please ensure all requirements are met before proceeding</p>
</div>

<div class="row">
    <div class="col-md-6">
        <h5>PHP Version</h5>
        <div class="requirement-item <?php echo $phpOk ? 'success' : 'danger'; ?>">
            <i class="fas fa-<?php echo $phpOk ? 'check-circle' : 'times-circle'; ?> me-2"></i>
            PHP <?php echo $phpVersion; ?> <?php echo $phpOk ? '(OK)' : '(Required: ' . $requiredVersion . '+)'; ?>
        </div>

        <h5 class="mt-4">Required Extensions</h5>
        <?php foreach ($extensions as $ext => $loaded): ?>
            <div class="requirement-item <?php echo $loaded ? 'success' : 'danger'; ?>">
                <i class="fas fa-<?php echo $loaded ? 'check-circle' : 'times-circle'; ?> me-2"></i>
                <?php echo strtoupper($ext); ?> <?php echo $loaded ? '(Loaded)' : '(Not Found)'; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="col-md-6">
        <h5>Directory Permissions</h5>
        <?php foreach ($directories as $dir => $writable): ?>
            <div class="requirement-item <?php echo $writable ? 'success' : 'danger'; ?>">
                <i class="fas fa-<?php echo $writable ? 'check-circle' : 'times-circle'; ?> me-2"></i>
                <?php echo basename($dir); ?> <?php echo $writable ? '(Writable)' : '(Not Writable)'; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($allOk): ?>
    <div class="text-center mt-4">
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> All requirements are met. You can proceed to the next step.
        </div>
        <a href="?step=2" class="btn btn-primary btn-lg">
            Continue to Database Setup <i class="fas fa-arrow-right"></i>
        </a>
    </div>
<?php else: ?>
    <div class="text-center mt-4">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> Please fix the issues above before proceeding.
        </div>
        <button type="button" class="btn btn-secondary" onclick="location.reload()">
            <i class="fas fa-refresh"></i> Recheck Requirements
        </button>
    </div>
<?php endif; ?>