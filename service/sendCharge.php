<?php

require __DIR__ . "/../vendor/autoload.php";

$subscription = new \Source\Models\CafeApp\AppSubscription();
$email = new \Source\Support\Email();
$view = new \Source\Core\View(__DIR__ . "/../shared/views/email");

$chargeNow = $subscription->find("pay_status = :status AND next_due = date(NOW()) AND last_charge != DATE(NOW())", "status=active")->fetch(true);

if ($chargeNow) {
	foreach ($chargeNow as $subscribe) {
		$user = (new \Source\Models\User())->findById($subscribe->user_id);
		$plan = $subscribe->plan();
		$card = $subscribe->creditCard();
		$transaction = $card->transaction($plan->price);

		$subscribe->last_charge = date("Y-m-d");

		if ($transaction) {
			$subscribe->status = "active";
			$subscribe->next_due = date("Y-m-d", strtotime($subscribe->next_due . "+{$plan->period}"));
			(new \Source\Models\CafeApp\AppOrder())->byCreditCard($user, $card, $subscribe, $transaction);
			$subject = "[PAGAMENTO CONFIRMADO] Obrigado por assinar o CaféApp";
			$body = $view->render("mail", [
				"subject" => $subject,
				"message" => "<h3>Obrigado {$user->first_name}!</h3><p>Estamos passando apenas para agradecer por você ser um assinante caféapp {$plan->name}.</p><p>Sua fatura deste mês venceu hojê e já está pago de acordo com seu plano. Qualquer dúvida estamos a disposição.</p>"
			]);

			$email->bootstrap(
				$subject,
				$body,
				$user->email,
				"{$user->first_name} {$user->last_name}"
			)->queue();
		} else {
			$subscribe->status = "past_due";
			(new \Source\Models\CafeApp\AppOrder())->byCreditCard($user, $card, $subscribe, $transaction);

			$subject = "[PAGAMENTO RECUSADO] Sua conta cafeapp precisa de atenção";
			$body = $view->render("mail", [
				"subject" => $subject,
				"message" => "<h3>Presado {$user->first_name}!</h3><p>Não conseguimos cobrar seu cartão referênte a fatura deste mês para sua assinatura caféapp. Precisamos que você veja isso.</p><p>Acesse sua conta para atualizar seus dados de pagamento, você pode cadastrar outro cartão.</p><p>Se não fizer nada agora uma nova tentativa de cobrança será feita em 3 dias. Se não der certo, sua assinatura será cancelada</p>"
			]);

			$email->bootstrap(
				$subject,
				$body,
				$user->email,
				"{$user->first_name} {$user->last_name}"
			)->queue();
		}

		$subscribe->save();
	}
}

//charge or cancel
$chargeDays = $subscription->find("pay_status = :status AND next_due + INTERVAL 3 DAY = date(NOW()) AND last_charge != DATE(NOW())", "status=active")->fetch(true);

if ($chargeDays) {
	foreach ($chargeDays as $subscribe) {
		$user = (new \Source\Models\User())->findById($subscribe->user_id);
		$plan = $subscribe->plan();
		$card = $subscribe->creditCard();
		$transaction = $card->transaction($plan->price);

		$subscribe->last_charge = date("Y-m-d");

		if ($transaction) {
			$subscribe->status = "active";
			$subscribe->next_due = date("Y-m-d", strtotime($subscribe->next_due . "+{$plan->period}"));
			(new \Source\Models\CafeApp\AppOrder())->byCreditCard($user, $card, $subscribe, $transaction);
			$subject = "[PAGAMENTO CONFIRMADO] Obrigado por assinar o CaféApp";
			$body = $view->render("mail", [
				"subject" => $subject,
				"message" => "<h3>Obrigado {$user->first_name}!</h3><p>Estamos passando apenas para agradecer por você ser um assinante caféapp {$plan->name}.</p><p>Sua fatura deste mês venceu hojê e já está pago de acordo com seu plano. Qualquer dúvida estamos a disposição.</p>"
			]);

			$email->bootstrap(
				$subject,
				$body,
				$user->email,
				"{$user->first_name} {$user->last_name}"
			)->queue();
		} else {
			$subscribe->status = "canceled";
			$subscribe->pay_status = "canceled";
			(new \Source\Models\CafeApp\AppOrder())->byCreditCard($user, $card, $subscribe, $transaction);

			$subject = "[ASSINATURA CANCELADA] Sua conta cafeapp agora é FREE";
			$body = $view->render("mail", [
				"subject" => $subject,
				"message" => "<h3>Que pena {$user->first_name}!</h3><p>Tentamos efetuar mais uma cobrança para sua assinatura {$plan->name} hojé, mas sem sucesso.</p><p>Como essa já é uma segunda tentativa, infelizmente sua assinatura foi cancelada. Mas calma, você pode assinar novamente a qualquer momento</p><p>Continue controlando com os recursos FREE, e assim que quiser basta assinar novamente e voltar de onde parou :)</p>"
			]);

			$email->bootstrap(
				$subject,
				$body,
				$user->email,
				"{$user->first_name} {$user->last_name}"
			)->queue();
		}

		$subscribe->save();
	}
}