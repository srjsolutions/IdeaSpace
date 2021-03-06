<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Theme;
use App\Space;
use App\Content\ContentType;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Event;
use Auth;
use Validator;
use Log;

class SpaceContentEditController extends Controller {


    private $contentType;


    /**
     * Create a new controller instance.
     *
     * @param ContentType $ct
     *
     * @return void
     */
    public function __construct(ContentType $ct) {

        $this->middleware('auth');
        //$this->middleware('register.theme.eventlistener');
        $this->contentType = $ct;
    }


    /**
     * The content edit page.
     *
     * @param int $space_id Space id.
     * @param String $contenttype Name of content type.
     * @param int $content_id Content id.
     *
     * @return Response
     */
    public function content_edit($space_id, $contenttype, $content_id) {

        //$theme_id = session('theme-id');        
        try {
            $space = Space::where('id', $space_id)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            abort(404);
        }

        try {
            $theme = Theme::where('id', $space->theme_id)->where('status', Theme::STATUS_ACTIVE)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return redirect()->route('space_add_select_theme');
        }

        $config = json_decode($theme->config, true);

        if (array_has($config, '#content-types.' . $contenttype)) {

            /* load and process content type and field content */
            $vars = $this->contentType->load($content_id, $config['#content-types'][$contenttype]);

        } else {

            abort(404);
        }

        $theme_mod = array();
        $theme_mod['theme-name'] = $config['#theme-name'];
        $theme_mod['theme-version'] = $config['#theme-version'];
        $theme_mod['theme-author-name'] = $config['#theme-author-name'];
        $theme_mod['theme-screenshot'] = url($theme->root_dir . '/' . Theme::SCREENSHOT_FILE);

        $form = array('form' => $vars);

        //$form['space_status'] = Space::STATUS_DRAFT;
        $form['space_id'] = $space_id;
        $form['theme'] = $theme_mod;
        $form['contenttype_name'] = $contenttype;
        $form['content_id'] = $content_id;

        $form['css'] = [
            asset('public/medium-editor/css/medium-editor.min.css'),
            asset('public/medium-editor/css/themes/bootstrap.min.css'),
            asset('public/assets/admin/space/content/css/content_add.css'),
        ];

        $form['js'] = [
            asset('public/vanilla-color-picker/vanilla-color-picker.min.js'),
            asset('public/medium-editor/js/medium-editor.min.js'),
            asset('public/assets/admin/space/content/js/content_add.js'),
        ];
        //Log::debug($vars);

        return response()->view('admin.space.content.content_edit', $form);
    }


    /**
     * Edit content submission.
     *
     * @param Request $request
     * @param int $space_id Space id.
     * @param String $contenttype Name of content type.
     * @param int $content_id Content id.
     *
     * @return Response
     */
    public function content_edit_submit(Request $request, $space_id, $contenttype, $content_id) {

        try {
            $space = Space::where('id', $space_id)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            abort(404);
        }

        try {
            $theme = Theme::where('id', $space->theme_id)->where('status', Theme::STATUS_ACTIVE)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return redirect()->route('space_add_select_theme');
        }

        $config = json_decode($theme->config, true);

        if (array_has($config, '#content-types.' . $contenttype)) {

            $validation_rules_messages = $this->contentType->get_validation_rules_messages($request, $config['#content-types'][$contenttype]);

            $validator = Validator::make($request->all(), $validation_rules_messages['rules'], $validation_rules_messages['messages']);

            if ($validator->fails()) {
                return redirect('admin/space/' . $id . '/edit/' . $contenttype . '/add')->withErrors($validator)->withInput();
            }


            $this->contentType->update($content_id, $contenttype, $config['#content-types'][$contenttype], $request->all());

        } else {

           abort(404);
        }

        return redirect('admin/space/' . $space_id . '/edit')->with('alert-success', trans('space_content_edit_controller.saved', ['label' => $config['#content-types'][$contenttype]['#label']]));
    }


}
