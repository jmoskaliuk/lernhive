<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * LernHive Flavour Setup — admin page to select and apply flavours.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_lernhive_flavour_setup');

$context = \core\context\system::instance();
require_capability('local/lernhive_flavour:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/lernhive_flavour/admin_flavour.php'));
$PAGE->set_title(get_string('page_title', 'local_lernhive_flavour'));
$PAGE->set_heading(get_string('page_title', 'local_lernhive_flavour'));

// Handle form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $action = optional_param('action', '', PARAM_ALPHA);
    if ($action === 'apply') {
        $flavour = required_param('flavour', PARAM_ALPHA);
        try {
            \local_lernhive_flavour\flavour_manager::set_flavour($flavour);
            $name = \local_lernhive_flavour\flavour_manager::get_flavour_definition($flavour)['label'];
            \core\notification::success(
                get_string('flavour_applied', 'local_lernhive_flavour', $name)
            );
        } catch (\Throwable $e) {
            \core\notification::error($e->getMessage());
        }
    }
}

$activeflavour = \local_lernhive_flavour\flavour_manager::get_active_flavour();
$flavours = \local_lernhive_flavour\flavour_manager::FLAVOURS;

echo $OUTPUT->header();
?>

<style>
    .lh-flavour-container {
        max-width: 1000px;
        margin: 40px auto;
    }

    .lh-flavour-intro {
        margin-bottom: 40px;
        text-align: center;
    }

    .lh-flavour-intro h2 {
        margin-bottom: 20px;
        font-size: 28px;
        font-weight: 600;
        color: #333;
    }

    .lh-flavour-intro p {
        margin-bottom: 0;
        font-size: 16px;
        color: #666;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }

    .lh-flavour-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }

    .lh-flavour-card {
        background: #fff;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
    }

    .lh-flavour-card:hover {
        border-color: #0066cc;
        box-shadow: 0 4px 16px rgba(0, 102, 204, 0.15);
    }

    .lh-flavour-card.active {
        border-color: #0066cc;
        border-width: 3px;
        background: #f0f7ff;
        box-shadow: 0 4px 16px rgba(0, 102, 204, 0.2);
    }

    .lh-flavour-icon {
        font-size: 48px;
        margin-bottom: 20px;
        display: block;
    }

    .lh-flavour-title {
        font-size: 24px;
        font-weight: 600;
        color: #333;
        margin-bottom: 12px;
    }

    .lh-flavour-desc {
        font-size: 14px;
        color: #666;
        margin-bottom: 30px;
        line-height: 1.6;
    }

    .lh-flavour-button {
        background: #0066cc;
        color: #fff;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s ease;
        width: 100%;
        max-width: 220px;
    }

    .lh-flavour-button:hover {
        background: #0052a3;
    }

    .lh-flavour-button.disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .lh-flavour-badge {
        display: inline-block;
        background: #0066cc;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        margin-bottom: 20px;
    }

    .lh-flavour-current {
        margin-top: 40px;
        padding: 20px;
        background: #f5f5f5;
        border-radius: 8px;
        text-align: center;
        font-size: 14px;
        color: #666;
    }
</style>

<div class="lh-flavour-container">
    <div class="lh-flavour-intro">
        <h2><?php echo get_string('page_title', 'local_lernhive_flavour'); ?></h2>
        <p><?php echo get_string('page_intro', 'local_lernhive_flavour'); ?></p>
    </div>

    <div class="lh-flavour-cards">
        <?php foreach ($flavours as $flavour): ?>
            <?php
                $def = \local_lernhive_flavour\flavour_manager::get_flavour_definition($flavour);
                $isactive = ($flavour === $activeflavour);
                $cardclass = $isactive ? 'lh-flavour-card active' : 'lh-flavour-card';
            ?>
            <div class="<?php echo $cardclass; ?>">
                <?php if ($isactive): ?>
                    <div class="lh-flavour-badge">
                        <?php echo get_string('btn_apply', 'local_lernhive_flavour'); ?>
                    </div>
                <?php endif; ?>

                <div class="lh-flavour-icon"><?php echo $def['icon']; ?></div>
                <div class="lh-flavour-title"><?php echo $def['label']; ?></div>
                <div class="lh-flavour-desc"><?php echo $def['desc']; ?></div>

                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="apply">
                    <input type="hidden" name="flavour" value="<?php echo $flavour; ?>">
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                    <button
                        type="submit"
                        class="lh-flavour-button <?php echo $isactive ? 'disabled' : ''; ?>"
                        <?php echo $isactive ? 'disabled' : ''; ?>
                    >
                        <?php echo $isactive ? get_string('current_flavour', 'local_lernhive_flavour', $def['label']) : get_string('btn_apply', 'local_lernhive_flavour'); ?>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="lh-flavour-current">
        <?php
            $activename = \local_lernhive_flavour\flavour_manager::get_flavour_definition($activeflavour)['label'];
            echo get_string('current_flavour', 'local_lernhive_flavour', $activename);
        ?>
    </div>
</div>

<?php
echo $OUTPUT->footer();
