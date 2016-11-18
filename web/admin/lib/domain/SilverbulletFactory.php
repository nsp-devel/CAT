<?php
namespace lib\domain;

/**
 * 
 * @author Zilvinas Vaira
 *
 */
class SilverbulletFactory {
    
    const COMMAND_ADD_USER = 'newuser';
    const COMMAND_SAVE = 'saveusers';
    const COMMAND_DELETE_USER = 'deleteuser';
    const COMMAND_ADD_CERTIFICATE = 'newcertificate';
    const COMMAND_REVOKE_CERTIFICATE = 'revokecertificate';
    
    const PARAM_EXPIRY = 'userexpiry';
    const PARAM_EXPIRY_MULTIPLE = 'userexpiry[]';
    const PARAM_ID = 'userid';
    const PARAM_ID_MULTIPLE = 'userid[]';
    
    const STATS_TOTAL = 'total';
    const STATS_ACTIVE = 'active';
    const STATS_PASSIVE = 'passive';
    
    /**
     *
     * @var \ProfileSilverbullet
     */
    private $profile;
    
    /**
     * 
     * @var SilverbulletUser []
     */
    private $users = array();
    
    /**
     *
     * @param \ProfileSilverbullet $profile
     */
    public function __construct($profile){
        $this->profile = $profile;
    }
    
    public function parseRequest(){
        if(isset($_POST[self::COMMAND_ADD_USER]) && !empty($_POST[self::COMMAND_ADD_USER])){
            $user = new SilverbulletUser($this->profile->identifier, $_POST[self::COMMAND_ADD_USER]);
            if(isset($_POST[self::PARAM_EXPIRY]) && !empty($_POST[self::PARAM_EXPIRY])){
                $user->setExpiry($_POST[self::PARAM_EXPIRY]);
                $user->save();
            }
        }elseif (isset($_POST[self::COMMAND_DELETE_USER])){
            $user = SilverbulletUser::prepare($_POST[self::COMMAND_DELETE_USER]);
            $user->delete();
            $this->redirectAfterSubmit();
        }elseif (isset($_POST[self::COMMAND_ADD_CERTIFICATE])){
            $user = SilverbulletUser::prepare($_POST[self::COMMAND_ADD_CERTIFICATE]);
            $user->load();
            $certificate = new SilverbulletCertificate($user);
            //$certificate->setCertificateDetails(rand(1000, 1000000), 'cert'.count($user->getCertificates()), $user->getExpiry());
            $certificate->save();
            $this->redirectAfterSubmit();
        }elseif (isset($_POST[self::COMMAND_REVOKE_CERTIFICATE])){
            $certificate = SilverbulletCertificate::prepare($_POST[self::COMMAND_REVOKE_CERTIFICATE]);
            $certificate->delete();
            $this->redirectAfterSubmit();
        }elseif (isset($_POST[self::COMMAND_SAVE])){
            $userIds = $_POST[self::PARAM_ID];
            $userExpiries = $_POST[self::PARAM_EXPIRY];
            foreach ($userIds as $key => $userId) {
                $user = SilverbulletUser::prepare($userId);
                $user->load();
                $user->setExpiry($userExpiries[$key]);
                $user->save();
            }
            $this->redirectAfterSubmit();
        }
    }
    
    private function redirectAfterSubmit(){
        if(isset($_SERVER['REQUEST_URI'])){
            header("Location: " . $_SERVER['REQUEST_URI'] );
            die();
        }
    }
    
    /**
     * 
     * @return \lib\domain\SilverbulletUser
     */
    public function createUsers(){
        $this->users = SilverbulletUser::getList($this->profile->identifier);
        return $this->users;
    }
    
    /**
     * 
     * @return array
     */
    public function getUserStats(){
        $count[self::STATS_TOTAL] = 0;
        $count[self::STATS_ACTIVE] = 0;
        $count[self::STATS_PASSIVE] = 0;
        foreach ($this->users as $user) {
            $count[self::STATS_TOTAL]++;
            if($user->isActive()){
                $count[self::STATS_ACTIVE]++;
            }else{
                $count[self::STATS_PASSIVE]++;
            }
        }
        return $count;
    }
}
