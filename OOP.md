#Introduction
Here is a list of the object that need to be created. Each new chapter represents an object.

**In case of data that needs to be fetched from DB, for getter methods, do something like this:**

    getValue(){
      if(this.value == null){
        this.setValue();
        return this.value;
      }
      else
        return this.value;
    }
    setValue(){
      this.value = mysqli_query("SELECT value from WHATEVER");
    }

In this way we do a "lazy" initialization of data.

#User
Vars:

  + Username (string)
  + Userid (int)
  + Roster (Roster)
  + Points (float)
  + Formations (array<Formation>)
  + Money (int)

Methods:

  + getUser
  + getUserID
  + getRoster
  + getPoints
  + getFormation
  + setFormation
  + getMoney

#Roster
Vars:

  + SoccerPlayers (array<SoccerPlayer>)
  + TotalCost (int)

Methods:

  + getPlayers
  + setPlayers
  + getPlayer
  + getTotalCost

#Formation
Vars:

  + SoccerPlayers (array<SoccerPlayer>)
  + matchID (int)
  + disposition (int OR enum if exists i.e 442, 343, 451)

Methods:

  + getSoccerPlayers
  + getSoccerPlayer
  + getMatchID
  + getDisposition

#SoccerPlayer
Vars:

  + name (string)
  + cost (int)
  + position (int OR enum if exists)
  + team (Team)

Methods:

  + getName
  + getCost
  + getPosition
  + getTeam