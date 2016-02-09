<?php
    require_once 'dbClass.php';
    class User {
        private $name=null,$id=null,$roster=null,$points=null,$formations=null,$money=null;
        private $db = null;

        function __construct($UID){
            $this->id = $UID;
            // $dbCls = new DB;
            $dbCls = new DB('root','','fantacalcio','localhost');
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

        public function getFormation($mid){
            foreach ($this->formations as $formation) {
                if($formation->getMID() == $mid)
                    return $formation;
            }
            return null;
        }

        public function getMoney(){
            if($this->money == null)
                $this->setMoney();
            return $this->money;
        }

        private function setUsername(){
            $stmt = $this->db->prepare("SELECT username FROM user WHERE UID=:id");
            $stmt->bindValue(':id',$this->id,PDO::PARAM_INT);
            $stmt->execute();
            if($stmt->rowCount() !== 1)
                throw new PDOException("setUsername failed, more than one record", 1);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->name = $result['username'];
        }

        private function setRoster(){
            //Make query
        }

        private function setPoints(){
            //Make query
        }

        private function setFormations(){
            //Make query
        }

    }
?>