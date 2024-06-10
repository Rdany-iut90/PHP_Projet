<?php

namespace App\Security;

use App\Entity\Event;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EventVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE]) && $subject instanceof Event;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user && $attribute !== self::VIEW) {
            return false;
        }

        /** @var Event $event */
        $event = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($event, $user);
            case self::EDIT:
                return $this->canEdit($event, $user);
            case self::DELETE:
                return $this->canDelete($event, $user);
        }

        return false;
    }

    private function canView(Event $event, $user): bool
    {
        
        return $event->isPublique() || ($user && $user === $event->getUser());
    }

    private function canEdit(Event $event, $user): bool
    {
        return $user === $event->getUser();
    }

    private function canDelete(Event $event, $user): bool
    {
        return $user === $event->getUser();
    }
}
