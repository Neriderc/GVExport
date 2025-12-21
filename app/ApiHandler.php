<?php

namespace vendor\WebtreesModules\gvexport;
use Exception;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Validator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Registry;


/**
 * Handles GVExport custom API calls from front end
 */
class ApiHandler
{
    var array $response_data = array();
    private ServerRequestInterface $request;
    private GVExport $module;
    private Tree $tree;
    private string $json_data;
    private $json;

    /**
     * @param ServerRequestInterface $request
     * @param GVExport $module
     * @param Tree $tree
     */
    public function __construct(ServerRequestInterface $request, GVExport $module, Tree $tree)
    {
        $this->request = $request;
        $this->module = $module;
        $this->tree = $tree;
        $this->json_data = Validator::parsedBody($this->request)->string('json_data');
        $this->json = json_decode($this->json_data, true);
    }

    /**
     * Convert response data into a data stream for returning response
     *
     * @return mixed
     */
    public function getResponse() {
        $stream = GVExport::getClass(StreamFactoryInterface::class)->createStream(json_encode($this->response_data));
        $response_factory = GVExport::getClass(ResponseFactoryInterface::class);
        return $response_factory->createResponse()
            ->withBody($stream)
            ->withHeader('Content-Type', "application/json");
    }

    /**
     * Process the $request data and return data stream of result
     *
     * @return mixed
     */
    public function handle() {
        if (json_last_error() === JSON_ERROR_NONE) {
            $type = FormSubmission::isNameStringValid($this->json['type']) ? $this->json['type'] : "";
            switch ($type) {
                case "save_settings":
                    $this->saveSettings();
                    break;
                case "get_settings":
                    $this->getSettings();
                    break;
                case "delete_settings":
                    $this->deleteSettings();
                    break;
                case "rename_settings":
                    $this->renameSettings();
                    break;
                case "is_logged_in":
                    $this->isLoggedIn();
                    break;
                case "get_tree_name":
                    $this->getTreeName();
                    break;
                case "get_saved_settings_link":
                    $this->getSavedSettingsLink();
                    break;
                case "load_settings_token":
                    $this->loadSettingsToken();
                    break;
                case "revoke_saved_settings_link":
                    $this->revokeSettingsToken();
                    break;
                case "add_my_favorite":
                    $this->addUserFavourite();
                    break;
                case "add_tree_favorite":
                    $this->addTreeFavourite();
                    break;
                case "get_help":
                    $this->getHelp();
                    break;
                case "get_shared_note_form":
                    $this->getSharedNoteForm();
                    break;
                case "get_record_count":
                    $this->getRecordCount();
                    break;
                    break;
                case "add_clippings_cart":
                    $this->addToClippingsCart();
                    break;
                default:
                    $this->response_data['success'] = false;
                    $this->response_data['json'] = $this->json_data;
                    $this->response_data['errorMessage'] = I18N::translate('Invalid request') . ": " . $type;
            }
        } else {
            $this->response_data['success'] = false;
            $this->response_data['json'] = $this->json_data;
            $this->response_data['errorMessage'] = I18N::translate('Invalid JSON') . ": " . json_last_error_msg();
        }

        return $this->getResponse();
    }

    /**
     *  Set a failure response to the API instance
     *
     * @param string $error
     * @param string $code
     * @return void
     */
    private function setFailResponse(string $error, string $code = "") {
        $this->response_data['success'] = false;
        $this->response_data['errorMessage'] = ($code === '' ? '' : $code . ": ") . I18N::translate($error);
    }

    /**
     * One of the API functions, this one is to save the provided settings
     *
     * @return void
     */
    public function saveSettings(): void
    {
        $vars = Validator::parsedBody($this->request)->array('vars');
        $formSubmission = new FormSubmission();
        $vars = $formSubmission->load($vars, $this->module);
        if (isset($this->json['settings_id']) && ctype_alnum((string) $this->json['settings_id']) && !in_array($this->json['settings_id'], [Settings::ID_ALL_SETTINGS, Settings::ID_MAIN_SETTINGS])) {
            if ($this->doesSettingsIdBelongsToUser()) {
                $id = $this->json['settings_id'];
            }
        }
        $settings = new Settings();
        if (!isset($this->json['main']) || $this->json['main']) {
            $id = Settings::ID_MAIN_SETTINGS;
        } else if (!isset($id) || $id == '') {
            $id = $settings->newSettingsId($this->module, $this->tree);
        }

        if ($id != "") {
            $this->response_data['settings_id'] = $id;
            $this->response_data['success'] = $settings->saveUserSettings($this->module, $this->tree, $vars, $id);
        } else {
            $this->setFailResponse('Failed to assign new settings ID', 'E10');
        }
    }


    /**
     * Return whether the settings ID belongs to the current user
     *
     * @return bool
     */
    private function doesSettingsIdBelongsToUser(): bool
    {
        $settings = new Settings();
        try {
            $settings->getSettingsJson($this->module, $this->tree, $this->json['settings_id']);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * One of the API functions, this one is to load the requested settings
     *
     * @return void
     */
    public function getSettings(): void
    {
        if (isset($this->json['settings_id']) && (ctype_alnum((string) $this->json['settings_id']) || in_array($this->json['settings_id'], [Settings::ID_ALL_SETTINGS, Settings::ID_MAIN_SETTINGS]))) {
            $settings = new Settings();
            // Treat logged-out users special if requesting ID_MAIN_SETTINGS
            if ($this->json['settings_id'] == Settings::ID_MAIN_SETTINGS && Auth::user()->id() == Settings::GUEST_USER_ID) {
                $vars = Validator::parsedBody($this->request)->array('vars');
                $form = new FormSubmission();
                $settings_response = $form->load($vars, $this->module);
                try {
                    $this->response_data['settings'] = $settings->getJsonFromSettings($settings_response, Settings::CONTEXT_MAIN_SETTINGS);
                    $this->response_data['success'] = true;
                } catch (Exception $e) {
                    $this->setFailResponse('Failed to receive settings JSON', 'E18');
                }
            } else {
                try {
                    $this->response_data['settings'] = ($this->json['settings_id'] == Settings::ID_ALL_SETTINGS ? $settings->getAllSettingsJson($this->module, $this->tree) : $settings->getSettingsJson($this->module, $this->tree, $this->json['settings_id']));
                    $this->response_data['success'] = true;
                } catch (Exception $e) {
                    $this->setFailResponse('Invalid JSON', 'E9');
                }
            }
        } else {
            $this->setFailResponse('Invalid settings ID', 'E6');
        }
    }

    /**
     * One of the API functions, this one is to generate a settings link for sharing
     *
     * @return void
     */
    public function getSavedSettingsLink(): void
    {
        if (isset($this->json['settings_id']) && (ctype_alnum((string) $this->json['settings_id']))) {
            $settings = new Settings();
            $link = $settings->getSettingsLink($this->module, $this->tree, $this->json['settings_id']);
            if ($link['success']) {
                $this->response_data['url'] = $link['url'];
                $this->response_data['success'] = true;
            } else {
                $this->setFailResponse($link['error'], 'E11');
            }
        } else {
            $this->setFailResponse('Invalid settings ID', 'E3');
        }
    }

    /**
     * One of the API functions, this one is to load settings from a token provided by a shared setting link
     *
     * @return void
     */
    public function loadSettingsToken(): void
    {
        if (isset($this->json['token']) && (ctype_alnum($this->json['token']))) {
            $settings = new Settings();
            try {
                $this->response_data['settings'] = $settings->loadSettingsToken($this->module, $this->tree, $this->json['token']);
                $this->response_data['success'] = true;
            } catch (Exception $e) {
                $this->setFailResponse('Invalid settings ID', 'E7');
            }
        } else {
            $this->setFailResponse('Invalid settings ID', 'E4');
        }
    }

    /**
     * One of the API functions, this one is to revoke a settings token, in the
     * case that a shared settings link has been deleted by the user
     *
     * @return void
     */
    private function revokeSettingsToken()
    {
        if (isset($this->json['token']) && (ctype_alnum($this->json['token']))) {
            $settings = new Settings();
            $link = $settings->revokeSettingsToken($this->module, $this->tree, $this->json['token']);
            if ($link) {
                $this->response_data['success'] = true;
            } else {
                $this->setFailResponse('Invalid', 'E2');
            }
        } else {
            $this->setFailResponse('Invalid settings ID', 'E5');
        }
    }

    /**
     * One of the API functions, this one is to delete one of the named settings
     * records saved by a user
     *
     * @return void
     */
    public function deleteSettings(): void
    {
        if (isset($this->json['settings_id']) && ctype_alnum((string) $this->json['settings_id']) && !in_array($this->json['settings_id'], [Settings::ID_ALL_SETTINGS, Settings::ID_MAIN_SETTINGS])) {
            if (Settings::isUserLoggedIn()) {
                $settings = new Settings();
                $settings->deleteUserSettings($this->module, $this->tree, $this->json['settings_id']);
                $this->response_data['success'] = true;
            } else {
                // Is user is not logged in, we should never have got this far
                $this->setFailResponse('Invalid', 'E12');
            }
        } else {
            $this->setFailResponse('Invalid settings ID', 'E8');
        }
    }
    /**
     * One of the API functions, this one is to rename one of the named settings
     * records saved by a user
     *
     * @return void
     */
    public function renameSettings(): void
    {
        if (isset($this->json['settings_id']) && ctype_alnum((string) $this->json['settings_id'])) {
            $id = $this->json['settings_id'];
            $new_name = $this->json['name'];
            $settings_obj = new Settings();
            try {
                if (Auth::user()->id() == Settings::GUEST_USER_ID) {
                    $this->setFailResponse('Not logged in', 'E15');
                } else {
                    $settings = $settings_obj->loadUserSettings($this->module, $this->tree, $id);
                    $settings['save_settings_name'] = $new_name;
                    $settings_obj->saveUserSettings($this->module, $this->tree, $settings, $id);
                }
                $this->response_data['success'] = true;
            } catch (Exception $e) {
                $this->setFailResponse('Invalid JSON', 'E16');
            }
        } else {
            $this->setFailResponse('Invalid settings ID', 'E17');
        }
    }

    /**
     * One of the API functions, this indicates if the user is currently logged in
     *
     * @return void
     */
    private function isLoggedIn()
    {
        if (Settings::isUserLoggedIn()) {
            $this->response_data['loggedIn'] = true;
        } else {
            $this->response_data['loggedIn'] = false;
        }
        $this->response_data['success'] = true;
    }

    /**
     * One of the API functions, this provides the name of the tree
     * @return void
     */
    private function getTreeName()
    {
        $this->response_data['treeName'] = e($this->tree->name());
        $this->response_data['success'] = true;
    }

    /**
     * One of the API functions, it adds a User favourite to webtrees
     *
     * @return void
     */
    private function addUserFavourite()
    {
        $this->addFavourite(Favourite::TYPE_USER_FAVOURITE);
    }

    /**
     * One of the API functions, it adds a Tree favourite to webtrees
     *
     * @return void
     */
    private function addTreeFavourite()
    {
        if (Auth::isManager($this->tree)) {
            $this->addFavourite(Favourite::TYPE_TREE_FAVOURITE);
        }
    }

    /**
     * Does the heavy lifting of adding a favourite of the provided type
     *
     * @param $type
     * @return void
     */
    private function addFavourite($type): void
    {
        if (isset($this->json['settings_id']) && (ctype_alnum((string) $this->json['settings_id']))) {
            $settings = new Settings();
            $link = $settings->getSettingsLink($this->module, $this->tree, $this->json['settings_id']);
            $name = $settings->getSettingsName($this->module, $this->tree, $this->json['settings_id']);
            if ($link['success']) {
                $favourite = new Favourite($type);
                if ($favourite->addFavourite($this->tree, $link['url'], $name)) {
                    $this->response_data['success'] = true;
                } else {
                    $this->setFailResponse('Invalid', 'E13');
                }
            } else {
                $this->setFailResponse($link['error'], 'E11');
            }
        } else {
            $this->setFailResponse('Invalid settings ID', 'E14');
        }
    }

    /**
     * Retrieve the help information from appropriate view file
     *
     * @return void
     */
    private function getHelp()
    {
        $help = new Help();
        $this->response_data['success'] = true;
        if ($help->helpExists($this->json['help_name'])) {
            $this->response_data['response'] = view($this->module->name() . '::MainPage/Help/' . $help->getHelpLocation($this->json['help_name']) . $this->json['help_name'],['module' => $this->module]);
        } else {
            // API call successful, even though help information not found
            $this->response_data['response'] = view($this->module->name() . '::MainPage/Help/' . $help->getHelpLocation(Help::NOT_FOUND) . Help::NOT_FOUND);
        }
    }

    /**
     * Retrieve the shared note form
     *
     * @return void
     */
    private function getSharedNoteForm()
    {
        $vars = Validator::parsedBody($this->request)->array('vars');
        $this->response_data['success'] = true;
        $this->response_data['response'] = view($this->module->name() . '::MainPage/Appearance/TileDesign/SharedNote', ['vars' => $vars, 'tree' => $this->tree, 'module' => $this->module]);
    }

    /**
     * Gets the number of records in the diagram
     */
    private function getRecordCount()
    {
        $this->response_data['success'] = true;
        $this->response_data['response'] = Settings::loadRecordCount($this->json['token']);
    }

    /**
     * Adds the relevant records to the clipping cart
     */
    private function addToClippingsCart()
    {
        $this->response_data['success'] = true;
        $this->response_data['response'] = ['json response here'];
    }
}