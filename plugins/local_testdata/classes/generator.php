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
 * Test data generator class for local_testdata.
 *
 * @package    local_testdata
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_testdata;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->dirroot . '/course/lib.php');
require_once($GLOBALS['CFG']->dirroot . '/user/lib.php');
require_once($GLOBALS['CFG']->libdir . '/questionlib.php');

use stdClass;
use dml_exception;

/**
 * Generator class for creating and managing test data.
 */
class generator {

    /** @var string Dataset name */
    private $dataset = '';

    /** @var int Number of items created */
    private $itemcount = 0;

    /** @var callable Progress callback function */
    private $progresscallback = null;

    /**
     * Constructor.
     *
     * @param string $dataset Dataset name
     * @param callable|null $progresscallback Optional callback for progress reporting
     */
    public function __construct($dataset = '', $progresscallback = null) {
        $this->dataset = $dataset;
        $this->progresscallback = $progresscallback;
    }

    /**
     * Report progress message.
     *
     * @param string $message Message to report
     */
    private function report($message) {
        if (is_callable($this->progresscallback)) {
            call_user_func($this->progresscallback, $message);
        }
    }

    /**
     * Track a created entity in the database.
     *
     * @param string $entitytype Type of entity (course, user, question, etc.)
     * @param int $entityid Moodle ID of entity
     * @param string|null $entityname Human-readable name
     */
    private function track_entity($entitytype, $entityid, $entityname = null) {
        global $DB;

        $item = new stdClass();
        $item->dataset = $this->dataset;
        $item->entitytype = $entitytype;
        $item->entityid = $entityid;
        $item->entityname = $entityname;
        $item->timecreated = time();

        try {
            $DB->insert_record('local_testdata_items', $item);
            $this->itemcount++;
        } catch (dml_exception $e) {
            $this->report("Warning: Could not track entity: " . $e->getMessage());
        }
    }

    /**
     * Create users.
     *
     * @param array $users Array of user objects with username, firstname, lastname, password
     * @return array Array of created user IDs
     */
    public function create_users(array $users) {
        global $DB;

        $userids = [];

        foreach ($users as $userdata) {
            try {
                // Ensure required fields
                if (empty($userdata['username']) || empty($userdata['firstname']) || empty($userdata['lastname'])) {
                    throw new \Exception('User must have username, firstname, and lastname');
                }

                $user = new stdClass();
                $user->username = $userdata['username'];
                $user->firstname = $userdata['firstname'];
                $user->lastname = $userdata['lastname'];
                $user->email = $userdata['email'] ?? ($userdata['username'] . '@example.com');
                $user->password = $userdata['password'] ?? 'Test1234!';
                $user->auth = $userdata['auth'] ?? 'manual';
                $user->confirmed = 1;
                $user->timemodified = time();

                // Use Moodle API to create user
                $userid = user_create_user($user, false, false);
                $userids[] = $userid;
                $this->track_entity('user', $userid, $user->username);

            } catch (\Exception $e) {
                $this->report("Error creating user: " . $e->getMessage());
            }
        }

        return $userids;
    }

    /**
     * Create courses.
     *
     * @param array $courses Array of course objects with fullname, shortname, category (optional)
     * @return array Array of created course IDs
     */
    public function create_courses(array $courses) {
        global $DB;

        $courseids = [];

        foreach ($courses as $coursedata) {
            try {
                // Ensure required fields
                if (empty($coursedata['fullname']) || empty($coursedata['shortname'])) {
                    throw new \Exception('Course must have fullname and shortname');
                }

                $courseobj = new stdClass();
                $courseobj->fullname = $coursedata['fullname'];
                $courseobj->shortname = $coursedata['shortname'];
                $courseobj->categoryid = $coursedata['category'] ?? 1;
                $courseobj->format = $coursedata['format'] ?? 'topics';
                $courseobj->summary = $coursedata['summary'] ?? '';
                $courseobj->startdate = $coursedata['startdate'] ?? time();

                // Use Moodle API to create course
                $course = create_course($courseobj);
                $courseids[] = $course->id;
                $this->track_entity('course', $course->id, $course->shortname);

            } catch (\Exception $e) {
                $this->report("Error creating course: " . $e->getMessage());
            }
        }

        return $courseids;
    }

    /**
     * Enrol users in a course.
     *
     * @param int $courseid Course ID
     * @param array $userids User IDs to enrol
     * @param string $role Role to assign (student, teacher, etc.)
     * @return int Number of successfully enrolled users
     */
    public function enrol_users($courseid, array $userids, $role = 'student') {
        global $DB;

        $enrolcount = 0;

        // Get the enrol method
        $enrolplugin = enrol_get_plugin('manual');
        if (!$enrolplugin) {
            $this->report("Error: Manual enrol plugin not available");
            return $enrolcount;
        }

        // Get course
        $course = get_course($courseid);

        foreach ($userids as $userid) {
            try {
                // Get enrol instance
                $enrolinstance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $courseid]);
                if (!$enrolinstance) {
                    $enrolplugin->add_instance($course);
                    $enrolinstance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $courseid]);
                }

                // Get role
                $roleid = $DB->get_field('role', 'id', ['shortname' => $role]);
                if (!$roleid) {
                    throw new \Exception("Role '$role' not found");
                }

                // Enrol user
                $enrolplugin->enrol_user($enrolinstance, $userid, $roleid);
                $this->track_entity('enrolment', $userid, "User $userid in Course $courseid");
                $enrolcount++;

            } catch (\Exception $e) {
                $this->report("Error enrolling user $userid: " . $e->getMessage());
            }
        }

        return $enrolcount;
    }

    /**
     * Create a question category.
     *
     * @param int $courseid Course ID (question context)
     * @param string $name Category name
     * @return int|null Category ID or null on failure
     */
    public function create_question_category($courseid, $name) {
        global $DB;

        try {
            $context = \core\context\course::instance($courseid);

            $category = new stdClass();
            $category->name = $name;
            $category->contextid = $context->id;
            $category->parent = 0;
            $category->sortorder = 0;
            $category->idnumber = '';
            $category->info = '';
            $category->infoformat = FORMAT_MOODLE;

            $categoryid = $DB->insert_record('question_categories', $category);
            $this->track_entity('question_category', $categoryid, $name);

            return $categoryid;

        } catch (\Exception $e) {
            $this->report("Error creating question category: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create multichoice questions.
     *
     * @param int $categoryid Question category ID
     * @param array $questions Array of question data
     * @return array Array of created question IDs
     */
    public function create_multichoice_questions($categoryid, array $questions) {
        global $DB;

        $questionids = [];

        foreach ($questions as $qdata) {
            try {
                // Validate required fields
                if (empty($qdata['name']) || empty($qdata['questiontext'])) {
                    throw new \Exception('Question must have name and questiontext');
                }

                // Get category context
                $category = $DB->get_record('question_categories', ['id' => $categoryid]);
                if (!$category) {
                    throw new \Exception("Category $categoryid not found");
                }

                // Create question record first.
                $question = new stdClass();
                $question->category = $categoryid;
                $question->parent = 0;
                $question->qtype = 'multichoice';
                $question->name = $qdata['name'];
                $question->questiontext = $qdata['questiontext'];
                $question->questiontextformat = FORMAT_HTML;
                $question->generalfeedback = $qdata['feedback'] ?? '';
                $question->generalfeedbackformat = FORMAT_HTML;
                $question->defaultmark = $qdata['defaultmark'] ?? 1.0;
                $question->penalty = $qdata['penalty'] ?? 0.3333333;
                $question->length = 1;
                $question->stamp = make_unique_id_code();
                $question->version = make_unique_id_code();
                $question->timecreated = time();
                $question->timemodified = time();
                $question->createdby = 2; // Admin user.
                $question->modifiedby = 2;
                $question->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

                $questionid = $DB->insert_record('question', $question);

                // Create question bank entry (Moodle 4.x+ Question Bank API).
                $entry = new stdClass();
                $entry->questioncategoryid = $categoryid;
                $entry->idnumber = null;
                $entry->ownerid = 2;

                $entryid = $DB->insert_record('question_bank_entries', $entry);

                // Create question version link.
                $qversion = new stdClass();
                $qversion->questionbankentryid = $entryid;
                $qversion->questionid = $questionid;
                $qversion->version = 1;
                $qversion->status = 'ready';

                $DB->insert_record('question_versions', $qversion);

                // Create multichoice options.
                $options = new stdClass();
                $options->questionid = $questionid;
                $options->layout = 0; // Vertical.
                $options->single = 1; // Single answer.
                $options->shuffleanswers = 1;
                $options->correctfeedback = 'Richtig!';
                $options->correctfeedbackformat = FORMAT_HTML;
                $options->partiallycorrectfeedback = '';
                $options->partiallycorrectfeedbackformat = FORMAT_HTML;
                $options->incorrectfeedback = 'Leider falsch.';
                $options->incorrectfeedbackformat = FORMAT_HTML;
                $options->answernumbering = 'abc';
                $options->shownumcorrect = 0;
                $options->showstandardinstruction = 0;

                $DB->insert_record('qtype_multichoice_options', $options);

                // Create answers.
                if (!empty($qdata['answer']) && !empty($qdata['wrong'])) {
                    $allanswers = array_merge([$qdata['answer']], $qdata['wrong']);
                    shuffle($allanswers);

                    foreach ($allanswers as $anstext) {
                        $ans = new stdClass();
                        $ans->question = $questionid;
                        $ans->answer = $anstext;
                        $ans->answerformat = FORMAT_PLAIN;
                        $ans->fraction = ($anstext === $qdata['answer']) ? 1.0 : 0.0;
                        $ans->feedback = ($anstext === $qdata['answer']) ? 'Richtig!' : 'Falsch.';
                        $ans->feedbackformat = FORMAT_HTML;

                        $DB->insert_record('question_answers', $ans);
                    }
                }

                $questionids[] = $questionid;
                $this->track_entity('question', $questionid, $qdata['name']);

            } catch (\Exception $e) {
                $this->report("Error creating question: " . $e->getMessage());
            }
        }

        return $questionids;
    }

    /**
     * Create an activity module.
     *
     * @param int $courseid Course ID
     * @param string $modulename Module name (e.g., leitnerflow, quiz, assign)
     * @param array $data Module data (name, section, settings, etc.)
     * @return int|null Module instance ID or null on failure
     */
    public function create_activity($courseid, $modulename, array $data) {
        global $DB;

        try {
            // Validate module exists
            if (!$DB->record_exists('modules', ['name' => $modulename])) {
                throw new \Exception("Module '$modulename' not found");
            }

            // Get course
            $course = get_course($courseid);

            // Prepare module info
            $moduleinfo = new stdClass();
            $moduleinfo->modulename = $modulename;
            $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => $modulename]);
            $moduleinfo->name = $data['name'] ?? 'Unnamed Activity';
            $moduleinfo->section = $data['section'] ?? 0;
            $moduleinfo->coursemodule = 0;
            $moduleinfo->visible = $data['visible'] ?? 1;
            $moduleinfo->course = $courseid;
            $moduleinfo->intro = $data['intro'] ?? '<p>' . ($data['name'] ?? 'Activity') . '</p>';
            $moduleinfo->introformat = FORMAT_HTML;

            // Add any module-specific settings.
            if (!empty($data['settings'])) {
                foreach ($data['settings'] as $key => $value) {
                    $moduleinfo->$key = $value;
                }
            }

            // Create the module.
            $cm = add_moduleinfo($moduleinfo, $course);

            $this->track_entity('course_module', $cm->coursemodule, $moduleinfo->name);

            return $cm->id;

        } catch (\Exception $e) {
            $this->report("Error creating activity: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Run a complete configuration to generate test data.
     *
     * @param array $config Configuration array
     * @return bool True on success, false on failure
     */
    public function run_config(array $config) {
        global $DB;

        try {
            // Set dataset name
            if (!empty($config['dataset'])) {
                $this->dataset = $config['dataset'];
            }

            if (empty($this->dataset)) {
                throw new \Exception('Dataset name is required');
            }

            $this->report("Loading configuration for dataset: {$this->dataset}");

            // Track created entities for later reference
            $createdusers = [];
            $createdcourses = [];
            $createdcategories = [];

            // Create users
            if (!empty($config['users'])) {
                $this->report("Creating " . count($config['users']) . " user(s)...");
                $createdusers = $this->create_users($config['users']);
                $this->report("Created " . count($createdusers) . " user(s).");
            }

            // Create courses and related content
            if (!empty($config['courses'])) {
                $this->report("Creating " . count($config['courses']) . " course(s)...");

                foreach ($config['courses'] as $coursedata) {
                    // Create course
                    $courseids = $this->create_courses([$coursedata]);
                    if (empty($courseids)) {
                        continue;
                    }
                    $courseid = $courseids[0];
                    $createdcourses[$coursedata['shortname']] = $courseid;

                    // Create question categories and questions
                    if (!empty($coursedata['questions'])) {
                        foreach ($coursedata['questions'] as $questiongroup) {
                            $categoryname = $questiongroup['category'] ?? 'Default';
                            $categoryid = $this->create_question_category($courseid, $categoryname);
                            $createdcategories[$categoryname] = $categoryid;

                            if ($categoryid && !empty($questiongroup['items'])) {
                                $this->report("Creating " . count($questiongroup['items']) . " question(s)...");
                                $this->create_multichoice_questions($categoryid, $questiongroup['items']);
                            }
                        }
                    }

                    // Enrol users
                    if (!empty($coursedata['enrol'])) {
                        $enrollusers = [];
                        if ($coursedata['enrol'] === '$all_users') {
                            $enrollusers = $createdusers;
                        } else if (is_array($coursedata['enrol'])) {
                            $enrollusers = $coursedata['enrol'];
                        }

                        if (!empty($enrollusers)) {
                            $this->report("Enrolling " . count($enrollusers) . " user(s) in course $courseid...");
                            $this->enrol_users($courseid, $enrollusers, 'student');
                        }
                    }

                    // Create activities
                    if (!empty($coursedata['activities'])) {
                        $this->report("Creating " . count($coursedata['activities']) . " activity(ies)...");

                        foreach ($coursedata['activities'] as $activitydata) {
                            // Handle special case for $auto question category reference
                            if (!empty($activitydata['settings']['questioncategoryid']) &&
                                $activitydata['settings']['questioncategoryid'] === '$auto') {
                                $firstcat = reset($createdcategories);
                                if ($firstcat) {
                                    $activitydata['settings']['questioncategoryid'] = $firstcat;
                                }
                            }

                            $this->create_activity($courseid, $activitydata['module'] ?? 'quiz', $activitydata);
                        }
                    }
                }
            }

            // Save dataset record
            $dataset = new stdClass();
            $dataset->name = $this->dataset;
            $dataset->description = $config['description'] ?? '';
            $dataset->configjson = json_encode($config);
            $dataset->itemcount = $this->itemcount;
            $dataset->timecreated = time();

            $existingset = $DB->get_record('local_testdata_sets', ['name' => $this->dataset]);
            if ($existingset) {
                $dataset->id = $existingset->id;
                $DB->update_record('local_testdata_sets', $dataset);
            } else {
                $DB->insert_record('local_testdata_sets', $dataset);
            }

            $this->report("Dataset '{$this->dataset}' created successfully with {$this->itemcount} items.");
            return true;

        } catch (\Exception $e) {
            $this->report("Error in run_config: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a dataset and all its entities.
     *
     * @param string $datasetname Dataset name to delete
     * @return bool True on success
     */
    public function delete_dataset($datasetname) {
        global $DB;

        try {
            $this->report("Deleting dataset: $datasetname");

            // Get all items for this dataset, ordered by ID DESC for reverse deletion
            $items = $DB->get_records('local_testdata_items',
                ['dataset' => $datasetname],
                'id DESC'
            );

            if (empty($items)) {
                $this->report("No items found for dataset: $datasetname");
                return true;
            }

            foreach ($items as $item) {
                try {
                    switch ($item->entitytype) {
                        case 'course':
                            delete_course($item->entityid, false);
                            $this->report("Deleted course: {$item->entityname}");
                            break;

                        case 'user':
                            $user = $DB->get_record('user', ['id' => $item->entityid]);
                            if ($user && !in_array($user->username, ['admin', 'guest'])) {
                                delete_user($user);
                                $this->report("Deleted user: {$item->entityname}");
                            }
                            break;

                        case 'question':
                            // Delete question and related records.
                            $question = $DB->get_record('question', ['id' => $item->entityid]);
                            if ($question) {
                                $DB->delete_records('question_answers', ['question' => $question->id]);
                                $DB->delete_records('qtype_multichoice_options', ['questionid' => $question->id]);
                                // Find and delete version + bank entry.
                                $qversions = $DB->get_records('question_versions', ['questionid' => $question->id]);
                                foreach ($qversions as $qv) {
                                    $DB->delete_records('question_bank_entries', ['id' => $qv->questionbankentryid]);
                                }
                                $DB->delete_records('question_versions', ['questionid' => $question->id]);
                                $DB->delete_records('question', ['id' => $question->id]);
                                $this->report("Deleted question: {$item->entityname}");
                            }
                            break;

                        case 'question_category':
                            $DB->delete_records('question_categories', ['id' => $item->entityid]);
                            $this->report("Deleted question category: {$item->entityname}");
                            break;

                        case 'course_module':
                            course_delete_module($item->entityid);
                            $this->report("Deleted module: {$item->entityname}");
                            break;

                        case 'enrolment':
                            // Enrolments are cleaned up with courses
                            break;

                        default:
                            $this->report("Unknown entity type: {$item->entitytype}");
                    }

                    // Remove tracking record
                    $DB->delete_records('local_testdata_items', ['id' => $item->id]);

                } catch (\Exception $e) {
                    $this->report("Error deleting entity {$item->id}: " . $e->getMessage());
                }
            }

            // Remove dataset record
            $DB->delete_records('local_testdata_sets', ['name' => $datasetname]);
            $this->report("Dataset '$datasetname' deleted successfully.");

            return true;

        } catch (\Exception $e) {
            $this->report("Error deleting dataset: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all datasets.
     *
     * @return array Array of dataset records
     */
    public function get_datasets() {
        global $DB;
        return $DB->get_records('local_testdata_sets', [], 'timecreated DESC');
    }

    /**
     * Get items for a dataset.
     *
     * @param string $datasetname Dataset name
     * @return array Array of item records
     */
    public function get_dataset_items($datasetname) {
        global $DB;
        return $DB->get_records('local_testdata_items',
            ['dataset' => $datasetname],
            'entitytype ASC, entityname ASC'
        );
    }
}
