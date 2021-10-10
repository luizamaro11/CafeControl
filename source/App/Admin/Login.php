<?php

namespace Source\App\Admin;

use Source\Core\Controller;
use Source\Core\View;
use Source\Models\Auth;
use Source\Models\User;

class Login extends Controller
{
    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../../themes/" . CONF_VIEW_ADMIN . "/");
    }

    public function root(): void
    {
        $user = Auth::user();

        if ($user && $user->level >= 5) {
            redirect("/admin/dash");
        } else {
            redirect("/admin/login");
        }
    }

    public function login(?array $data): void
    {
        $user = Auth::user();

        if ($user && $user->level >= 5) {
            redirect("/admin/dash");
        }

        if (!empty($data["email"]) && !empty($data["password"])) {
            if (request_limit("loginLogin", 3, 5 * 60)) {
                $json["message"] = $this->message->error("ACESSO NEGADO:  Aguarde por 5 minutos para tentar novamente.")->render();
                echo json_encode($json);
                return;
            }

            $auth = new Auth();
            $login = $auth->login($data["email"], $data["password"], 5);

            if ($login) {
                $json["redirect"] = url("/admin/dash");
            } else {
                $json["redirect"] = $auth->message()->render();
            }

            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
            CONF_SITE_NAME . " | Admin",
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/login/login", [
            "head" => $head
        ]);
    }
}