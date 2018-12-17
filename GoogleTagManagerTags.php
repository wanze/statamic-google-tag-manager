<?php

namespace Statamic\Addons\GoogleTagManager;

use Illuminate\Http\Request;
use Statamic\API\Role;
use Statamic\API\Str;
use Statamic\Data\Services\UserGroupsService;
use Statamic\Config\Addons;
use Statamic\Contracts\Data\Users\User;
use Statamic\Extend\Tags;

/**
 * Tags for the GoogleTagManager addon.
 */
class GoogleTagManagerTags extends Tags
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Addons
     */
    private $addons;

    /**
     * @var UserGroupsService
     */
    private $userGroupsService;

    /**
     * @param Request $request
     * @param Addons $addons
     * @param UserGroupsService $userGroupsService
     */
    public function __construct(Request $request, Addons $addons, UserGroupsService $userGroupsService)
    {
        parent::__construct();

        $this->request = $request;
        $this->addons = $addons;
        $this->userGroupsService = $userGroupsService;
    }

    /**
     * Build the {{ google_tag_manager:head }} tag.
     *
     * Contains the GTM javascript inserted in the <head> tag.
     *
     * @return string
     */
    public function head()
    {
        return $this->shouldExclude() ? '' : $this->getMarkupHead();
    }

    /**
     * The the {{ google_tag_manager:body }} tag.
     *
     * Contains the GTM javascript inserted after the opening body tag.
     *
     * @return string
     */
    public function body()
    {
        return $this->shouldExclude() ? '' : $this->getMarkupBody();
    }

    public function bootstrap()
    {
        // Empty on purpose: Overwrite parent::bootstrap to simplify unit testing this class.
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    private function config($key = null, $default = null)
    {
        $config = $this->addons->get(Str::snake($this->addon_name)) ?: [];

        if ($key === null) {
            return $config;
        }

        return isset($config[$key]) ? $config[$key] : $default;
    }

    /**
     * @return bool
     */
    private function shouldExclude() {
        if ($this->shouldExcludeByPath()) {
            return true;
        }

        if ($this->shouldExcludeByCurrentUser()) {
            return true;
        }

        return false;
    }

    /**
     * Check if the GTM snippets should be excluded for the current user.
     *
     * @return bool
     */
    private function shouldExcludeByCurrentUser()
    {
        if (!$this->request->user()) {
            return false;
        }

        // Bail early if GTM is excluded for all authenticated users.
        if (bool($this->config('exclude_authenticated', true))) {
            return true;
        }

        /** @var User $user */
        $user = $this->request->user();

        if ($this->shouldExcludeByRole($user)) {
            return true;
        }

        if ($this->shouldExcludeByGroup($user)) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    private function shouldExcludeByRole(User $user)
    {
        $excludedRoles = collect($this->config('exclude_user_roles', []));

        if (!$excludedRoles->count()) {
            return false;
        }

        foreach ($excludedRoles as $role) {
            if ($user->hasRole(Role::whereHandle($role))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    private function shouldExcludeByGroup(User $user)
    {
        $excludedGroups = collect($this->config('exclude_user_groups', []));

        if (!$excludedGroups->count()) {
            return false;
        }

        foreach ($excludedGroups as $group) {
            if ($user->inGroup($this->userGroupsService->handle($group))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the GTM snippets should be excluded on the current path.
     *
     * @return bool
     */
    private function shouldExcludeByPath()
    {
        $excludedPaths = collect($this->config('exclude_paths', []));
        if (!$excludedPaths->count()) {
            return false;
        }

        $path = $this->request->getPathInfo();

        foreach ($excludedPaths as $excludedPath) {
            if ($excludedPath === $path) {
                return true;
            }

            // Check if path matches a wildcard path such as /foo*.
            if (Str::endsWith($excludedPath, '*') && Str::startsWith($path, Str::substr($excludedPath, 0, -1))) {
                return true;
            }
        }

        return false;
    }

    private function getMarkupBody()
    {
        $markup = "<!-- Google Tag Manager (noscript) -->
                   <noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=%s\"
                   height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
                   <!-- End Google Tag Manager (noscript) -->";

        return sprintf($markup, $this->config('container_id', ''));
    }

    private function getMarkupHead() {
        $markup = "<!-- Google Tag Manager -->
                  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                  })(window,document,'script','%s','%s');</script>
                  <!-- End Google Tag Manager -->";

        return sprintf(
            $markup,
            $this->config('data_layer_variable', 'dataLayer'),
            $this->config('container_id', '')
        );
    }
}
