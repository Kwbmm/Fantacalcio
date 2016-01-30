#Introduction
Here is a list of the object that need to be created. Each new chapter represents an object.

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