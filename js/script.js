/*
 * Description: Budget application...
 *
 * Developers: Daniel Clough, Michael Park, Diane Zevenbergen
 * 
 * 
 *
 */

// ---------------INITIALIZE--------------------//

$(document).ready(function() {

  // If url contains hashed email, check if valid and
  // if so prompt user for password creation/reset
  LCB.controller.checkUrl();
  console.log(LCB);
  
  // Hide worksheets so only start screen displays
  LCB.view.togglePages('#m_home');
  LCB.view.defDate();
  LCB.view.userAcct(null);
  
});
