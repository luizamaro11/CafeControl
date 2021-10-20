<?php

namespace Source\App\Admin;

use Source\Models\Post;
use Source\Models\Category;
use Source\Models\CafeApp\AppSubscription;
use Source\Models\CafeApp\AppPlan;


class Dash extends Admin
{
    public function __construct()
    {
        parent::__construct();
    }

    public function dash(): void
    {
        redirect("/admin/dash/home");
    }

    public function home(?array $data): void
    {
        if (!empty($data["refresh"])) {
            $list = null;
            $items = (new Online())->findByActive();
            if ($items) {
                foreach ($items as $item) {
                    $list[] = [
                        "dates" => date_fmt($item->created_at, "H\hi") . " - " . date_fmt($item->updated_at, "H\hi"),
                        "user" => ($item->user ? $item->user()->full_name() : "Guest user"),
                        "pages" => $item->pages,
                        "url" => $item->url 
                    ];
                }
            }

            return;
        }

        $head = $this->seo->render(
            CONF_SITE_NAME . " | Dashboard",
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/dash/home", [
            "app" => "dash",
            "head" => $head,
            "control" => (object)[
                "subscribers" => (new AppSubscription())->find("pay_status = :s", "s=active")->count(),
                "plans" => (new AppPlan())->find("status = :s", "s=active")->count(),
                "recurrence" => (new AppSubscription())->recurrence()
            ],
            "blog" => (object)[
                "posts" => (new Post())->find("status = 'post'")->count(),
                "drafts" => (new Post())->find("status = 'draft'")->count(),
                "categories" => (new Category())->find("type = 'post'")->count()
            ],
            "users" => (object)[
                "users" => (new Post())->find("level < 5")->count(),
                "admins" => (new Post())->find("level >= 5")->count()
            ],
            "online" => "",
            "onlineCount" => ""
        ]);
    }

    public function logoff(): void
    {
        $this->message->success("Você saiu com sucesso {$this->user->first_name}.")->flash();

        Auth::logout();
        redirect("/admin/login");
    }
}