<?php
    /**
     * Vars:
     *
     *  + Username (string)
     *  + Userid (int)
     *  + Roster (Roster)
     *  + Points (float)
     *  + Formations (array<Formation>)
     *  + Money (int)
     *
     * Methods:
     *
     *  + getUsername
     *  + getUserID
     *  + getRoster
     *  + getPoints
     *  + getFormation
     *  + setFormation
     *  + getMoney
     */
    class User
    {
        private $name=null,$id=null,$roster=null,$points=null,$formations=null,$money==null;

        public function getUsername(){
            if($this->name == null)
                $this->setUsername();
            return $this->name;
        }
        public function getUserID(){
            if($this->id==null)
                $this->setUserID();
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
            //Make query
        }

        private function setUserID(){
            //Make query
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