<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/api/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Display & process form to request a password reset.
     */
    #[Route('/request', name: 'app_forgot_password_request', methods: ["POST"])]
    public function request(Request $request, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        $data = $request->toArray();

        $emailInput = $data["email"] ?? null;

        if(!is_string($emailInput)) {
            return $this->json([
                "data" => [
                    "message" => "Password reset link sent!",
                ]
            ], Response::HTTP_ACCEPTED);  
        }

        $email = filter_var(trim($emailInput), FILTER_VALIDATE_EMAIL);

        if($email === false) {
            return $this->json([
                "data" => [
                    "message" => "Password reset link sent!",
                ]
            ], Response::HTTP_ACCEPTED);
        }

        return $this->processSendingPasswordResetEmail($email, $mailer, $translator);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    /*
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }
    */

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route('/reset', name: 'app_reset_password', methods: ["POST"])]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        $data = $request->toArray();

        $token = $data["token"] ?? null;

        if(!is_string($token) || trim($token) === "") {
            return $this->json([
                "data" => [
                    "errors" => "Token required.",
            ]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->json([], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $newPasswordInput = $data["password"] ?? null;

        if(!is_string($newPasswordInput) || trim($newPasswordInput) === "") {
            return $this->json([
                "data" => [
                    "errors" => "Password required.",
                ]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        $user->setPlainPassword($newPasswordInput);
        $violations = $validator->validateProperty($user, "plainPassword", ["registration"]);

        $errors = [];

        if($violations->count() > 0) {
            foreach($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json([
                "errors" => $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPasswordInput));

        $this->resetPasswordHelper->removeResetRequest($token);

        $this->entityManager->flush();

        return $this->json([], Response::HTTP_OK);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        if($user === null) {
            return $this->json(["user is null"]);
        }

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->json([
                "data" => [
                    "message" => "Password reset link sent!",
                ]
            ], Response::HTTP_ACCEPTED);
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'app_forgot_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            // $this->addFlash('reset_password_error', sprintf(
            //     '%s - %s',
            //     $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE, [], 'ResetPasswordBundle'),
            //     $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            // ));

            return $this->json([
                "data" => [
                    "message" => "Password reset link sent!",
                ]
            ], Response::HTTP_ACCEPTED);
        }

        $email = (new TemplatedEmail())
            ->from(new Address('eminoviciidarius@gmail.com', 'petrut darius'))
            ->to((string) $user->getEmail())
            ->subject('Your password reset request')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        $mailer->send($email);
        
        return $this->json([
            "data" => [
                "message" => "Password reset link sent!",
            ]
        ], Response::HTTP_ACCEPTED);
    }
}
