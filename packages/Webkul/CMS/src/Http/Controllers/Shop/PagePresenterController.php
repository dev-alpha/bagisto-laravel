<?php

namespace Webkul\CMS\Http\Controllers\Shop;

use Webkul\CMS\Http\Controllers\Controller;
use Webkul\CMS\Repositories\CmsRepository;

class PagePresenterController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\CMS\Repositories\CmsRepository  $cmsRepository
     * @return void
     */
    public function __construct(protected CmsRepository $cmsRepository)
    {
        $this->_config = request('_config');
    }

    /**
     * To extract the page content and load it in the respective view file
     *
     * @param  string  $urlKey
     * @return \Illuminate\View\View
     */
    public function presenter($urlKey)
    {
        $page = $this->cmsRepository->findByUrlKeyOrFail($urlKey);

        return view('shop::cms.page')->with('page', $page);
    }

    /**
     * Preview the CMS page from datagrid.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function view($id)
    {
        $page = $this->cmsRepository->findOrFail($id);
        
        request()->merge(['url_key' => $page->url_key]);
        
        if (is_null($page)) {
            abort(404);
        }

        return view($this->_config['view'], compact('page'));
    }
}