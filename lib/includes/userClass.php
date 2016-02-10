<?php
    /**
     * Class managing users.
     * Initialization of the data is 'lazy', i.e, only when get method is called the
     * required object is created and returned.
     * 
     * @author Marco Ardizzone
     * @link twitter.com/marcoardiz
     */

    require_once 'dbClass.php';
    require_once 'formationClass.php';

    class User {
        private $name=null,$id=null,$roster=null,$points=null,$formations=null,$money=null;
        private $db = null;

        /**
         * Default constructor for initializing the User object.
         * @param int $UID      User ID of the user
         * @param DB  $customDB Use a custom DB object for this User
         */
        function __construct($UID,$customDB=null){
            $this->id = $UID;
            if($customDB === null)
                $dbCls = new DB;
            else
                $dbCls = $customDB;
            $this->db = $dbCls->getDB();
        }

        public function getUsername(){
            if($this->name == null)
                $this->setUsername();
            return $this->name;
        }
        public function getUserID(){
            return $this->id;
        }

        public function getRoster(){
            if($this->roster==null)
                $this->setRoster();
            return $this->roster;
        }

        public function getPoints(){
            if($this->points == null)
                $this->setPoints();
            return $this->points;
        }

        public function getFormations(){
            if($this->formations == null)
                $this->setFormations();
            return $this->formations;
        }

        /**
         * Looks for the formation of a particular match day and returns it
         * @param  int  $mid    The match ID
         * @return Formation    The requested formation or null if not found.
         */
        public function getFormation($mid){
            foreach ($this->formations as $formation) {
                if($formation->getMID() == $mid)
                    return $formation;
            }
            return null; //Not found
        }

        public function getMoney(){
            if($this->money == null)
                $this->setMoney();
            return $this->money;
        }

        private function setUsername(){
            $s = $this->db->prepare("SELECT username FROM user WHERE UID=:id");
            $s->bindValue(':id',$this->id,PDO::PARAM_INT);
            $s->execute();
            if($s->rowCount() !== 1)
                throw new PDOException("setUsername failed, more than one record", 1);
            $result = $s->fetch(PDO::FETCH_ASSOC);
            $this->name = $result['username'];
        }

        private function setRoster(){
            $this->roster = new Roster($this->id);
        }

        private function setPoints(){
            $s = $this->db->prepare("SELECT points FROM scores WHERE UID=:id");
            $s->bindValue(':id',$this->id,PDO::PARAM_INT);
            $s->execute();
            if($s->rowCount() !== 1)
                throw new PDOException("setPoints failed, more than one record",3);
            $result = $s->fetch(PDO::FETCH_ASSOC);
            $this->points = $result['points'];
        }

        private function setFormations(){
            $this->formations = array();
            $s = $this->db->prepare("
                SELECT MID, SPID, disposition
                FROM user_formation
                WHERE UID=:id");
            $s->bindValue(':id',$this->id,PDO::PARAM_INT);
            $s->execute();
            $results = $s->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
            foreach ($results as $mid => $players)
                array_push($this->formations, new Formation($mid,$players));
        }

        private function setMoney(){
            $s = $this->db->prepare("SELECT money FROM user WHERE UID=:id");
            $s->bindValue(':id',$this->id,PDO::PARAM_INT);
            $s->execute();
            if($s->rowCount() !== 1)
                throw new PDOException("setPoints failed, more than one record",3);
            $result = $s->fetch(PDO::FETCH_ASSOC);
            $this->points = $result['money'];
        }

    }
?>