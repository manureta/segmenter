 CREATE OR REPLACE FUNCTION indec.insertar_geometrias(poligono geometry=null,punto geometry=null)                            
  RETURNS integer                                                                         
  LANGUAGE plpgsql                                                                        
 AS $function$                                                                            
                                                                                          
 DECLARE newid integer;                                                                   
 begin                                                                                    
  INSERT INTO geometrias (poligono,punto) VALUES ($1,$2) RETURNING id INTO newid;                       
  return newid;                                                                           
                                                                                          
 end;                                                                                     
                                                                                          
 $function$                                                                               

