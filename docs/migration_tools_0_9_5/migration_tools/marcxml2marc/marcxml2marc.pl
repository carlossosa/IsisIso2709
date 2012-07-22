#!/usr/bin/perl -w

use strict;

use XML::Simple;
$XML::Simple::PREFERRED_PARSER='XML::Parser';

use Encode;
use utf8; # for utf8-strings in this cod

use MARC::Record;
use MARC::Field;
use MARC::Charset;

my %fields;
my $marker;
my %control_tags=(('001','0'),('005','0'));
my $marcxml_file;

if($ENV{'QUERY_STRING'})
   {($marcxml_file)=split(/\?/,$ENV{'QUERY_STRING'},1);}
else
   {$marcxml_file=$ARGV[0];}

my $Unimarc_Slim_record;
# ////////
sub extract_field_structure_Unimarc_Slim_decode_utf8()  # $Unimarc_Slim_record 
   { # &extract_field_structure_Unimarc_Slim()
   undef %fields;
   undef $marker;
   my %fields_hash=%$Unimarc_Slim_record;
   my $field;
   my $subfield;
   my %sub_hash;
   #
   if(exists $fields_hash{'leader'})   
      {
      my $leaders=$fields_hash{'leader'};
      if($leaders)
         {
         foreach my $leader (@$leaders)
            {
            my %leader_hash=%$leader;
            $marker=$leader_hash{'content'};
            }         
         }      
      }
   if(exists $fields_hash{'control'})   
      {my $controls=$fields_hash{'control'};
      if($controls)
         {foreach my $control (@$controls)
            {my %control_hash=%$control;
            if(exists $control_hash{'tag'})
               {my $label;
               eval{$label=&decode_utf8($control_hash{'tag'})}; 
               if($@){print "<font color=\"red\">Error: &decode_utf8(".$control_hash{'tag'}.") have problem!!!</font><br>\n";exit};
               $control_tags{$label}=0;
               if(exists $control_hash{'content'})
                  {$field=$control_hash{'content'};
                  #eval{$field=&decode_utf8($control_hash{'content'})}; 
                  #if($@){print "<font color=\"red\">Error: &decode_utf8(".$control_hash{'content'}.") have problem!!!</font><br>\n";exit};
                  #print 'field="<b>'.&encode_utf8($field)."</b>\"<br>\n";
                  $sub_hash{'content'}=[$field];}                  
               my $field_item={%sub_hash};
               if(exists $fields{$label})
                  {push @{$fields{$label}},$field_item
                  }else
                  {$fields{$label}=[$field_item]}
               undef %sub_hash;                                     
               } 
            }  
         }
      }
   if(exists $fields_hash{'field'})
      {if($fields_hash{'field'})
         {foreach my $field_hash (@{$fields_hash{'field'}})
            {if(exists $field_hash->{'tag'})
               {my $label;
               eval{$label=&decode_utf8($field_hash->{'tag'})};  
               if($@){print "<font color=\"red\">Error: &decode_utf8(".$field_hash->{'tag'}.") have problem!!!</font><br>\n";exit};
               my $content='';            
               if(exists $field_hash->{'i1'}) 
                  {eval{$field=&decode_utf8($field_hash->{'i1'})}; 
                  if($@){print "<font color=\"red\">Error: &decode_utf8(".$field_hash->{'i1'}.") have problem!!!</font><br>\n";exit};
                  $content.=$field;
                  if($field ne '')
                     {$sub_hash{'ind1'}=[$field];}                                    
                  }
               if(exists $field_hash->{'i2'}) 
                  {$field=$field_hash->{'i2'};
                  #eval{$field=&decode_utf8($field_hash->{'i2'})};  
                  #if($@){print "<font color=\"red\">Error: &decode_utf8(".$field_hash->{'i2'}.") have problem!!!</font><br>\n";exit};
                  $content.=$field;
                  if($field ne '')
                     {$sub_hash{'ind2'}=[$field];}                     
                  }               
               if(exists $field_hash->{'subfield'}) 
                  {foreach my $subfield_hash (@{$field_hash->{'subfield'}})
                     {if (exists $subfield_hash->{'code'}) 
                        {my $submark;
                         eval{$submark='^'.&decode_utf8($subfield_hash->{'code'})}; 
                         if($@){print "<font color=\"red\">Error: &decode_utf8(".$subfield_hash->{'code'}.") have problem!!!</font><br>\n";exit};
                         if (exists $subfield_hash->{'content'}) 
                           {#eval{$subfield=&decode_utf8($subfield_hash->{'content'})};
                           #print &encode_utf8($subfield_hash->{'content'})."\n";
                           $subfield=$subfield_hash->{'content'};
                           
                           #if($@){print "<font color=\"red\">Error: &decode_utf8(".$subfield_hash->{'content'}.") have problem!!!</font><br>\n";exit};
                           $content.=$submark.$subfield;}      
                        if(exists $sub_hash{$submark})
                           {push @{$sub_hash{$submark}},$subfield
                           }else
                           {$sub_hash{$submark}=[$subfield];
                           #print 'subfield="<b>'.&encode_utf8($subfield)."</b>\"<br>\n"; #(PRINT)
                           }
                        }
                     }
                  }
               if(exists $field_hash->{'content'})
                  {eval{$field=&decode_utf8($field_hash->{'content'})}; 
                  if($@){print "<font color=\"red\">Error: &decode_utf8(".$field_hash->{'content'}.") have problem!!!</font><br>\n";exit};
                  $sub_hash{'content'}=[$field];                  
                  }else
                  {$sub_hash{'content'}=[$content];}
               my $field_item={%sub_hash};   
               if(exists $fields{$label})
               {push @{$fields{$label}},$field_item
               }else
               {$fields{$label}=[$field_item]}
               undef %sub_hash;}
            }
         }
      }   
   }
# \\\\\\\\

# ////////
sub issubfield($$$) 
   { # &issubfield(TAG,OCC,SUBMARK)
   my $tag=shift;
   my $occ=shift;
   my $submark=shift;
   if(exists $fields{$tag})
     {my $field=$fields{$tag};
     if($field)
        {my @array=@$field;
        if(exists $array[$occ])
           {my $item=$array[$occ];
           if($item)
              {my %hash=%$item;
              if(defined $submark and $submark ne '')
                 {if(exists $hash{$submark})
                    {if($hash{$submark} ne '')
                       {my @array2=@{$hash{$submark}};
                       if(exists $array2[0])
                          {if($array2[0] ne "")
                             {return 1}
                          }
                       }
                    }
                 }else
                 {
                 if(exists $hash{'content'})
                    {if($hash{'content'} ne '')
                       {my @array2=@{$hash{'content'}};
                       if(exists $array2[0])
                          {if($array2[0] ne "")
                             {return 1}
                          }
                       }
                    }
                 }
                            
              }
           }
        };     
     };   
   return 0
   }
# \\\\\\\\

my $marc_file=$marcxml_file.".mrc";
open(my $MARCFILE, ">", $marc_file) or die "Can't open $marc_file: $!";
unless(-e $marc_file)
   {print "<font color=red>Error: HTML-file <b>$marc_file</b> not found (not created)!</font>\n";exit;}

my $xml_simple = XML::Simple->new();                        
my $xml_document;
eval {$xml_document = $xml_simple->XMLin($marcxml_file,forcearray => 1,forcecontent => 1)};
if($@)
   {print "<font color=\"red\">Error: XML::Simple routine can't read xml-file: <b>$marcxml_file</b>!!!</font><br>\n";exit};
my @records=@{$xml_document->{'record'}};
my $number_of_record=scalar @records;
foreach my $record_count(0..$number_of_record-1)
            {
            my $record_MARC;
            eval{$record_MARC = MARC::Record->new()};
            if($@)
               {print "<font color=\"red\">Error: MARC::Record->new() routine some problem!!!</font><br>\n";exit};            
            $Unimarc_Slim_record=$records[$record_count];
            print "extract field structure MARC xml slim for record ".($record_count+1)." (";
            &extract_field_structure_Unimarc_Slim_decode_utf8();  
            print "Ok)\n";
            $record_MARC->leader($marker);
            $record_MARC->encoding('UTF-8');               
            #print "&#1047;&#1072;&#1087;&#1080;&#1089; &#8470;".($record_count+1)." &#1076;&#1083;&#1103; &#1092;&#1072;&#1081;&#1083;&#1091; (".($count+1)."/$xml_files_count) $xml_file_path<br>\n";
            #print $TOTALXMLFILE " <record xml_file=\"".$xml_file_path."\" record_number=\"".($record_count+1)."\" counter=\"".($count+1)."/$xml_files_count\">\n" if $v;
            
            foreach my $label(sort keys %fields)
               {my $tag=$label;
               $tag='00'.$tag if (length $tag)==1;
               $tag='0'.$tag if (length $tag)==2; 
               my $occ=0;
               foreach my $field(@{$fields{$label}})
                  {my $field_MARC;
                  my $field_content=''; 
                  my $field_I1=''; 
                  my $field_I2='';
                  if(&issubfield($label,$occ,'ind1'))
                     {$field_I1=$fields{$label}->[$occ]->{'ind1'}->[0];
                     #print  "&nbsp;$label:i1=".&encode_utf8($field_I1).'<br>';
                     }
                  if(&issubfield($label,$occ,'ind2'))
                     {$field_I2=$fields{$label}->[$occ]->{'ind2'}->[0];
                     #print  "&nbsp;$label:i2=".&encode_utf8($field_I2).'<br>';
                     }
                  #print $TOTALXMLFILE '  <field tag="'.&encode_utf8($label).'" i1="'.&encode_utf8($field_I1).'" i2="'.&encode_utf8($field_I2).'">' if $v;    
                  if(&issubfield($label,$occ,'content') and (exists $control_tags{$label}))
                     {$field_content=$fields{$label}->[$occ]->{'content'}->[0];
                     #print $TOTALXMLFILE &encode_utf8($field_content);
                     #print  "&nbsp;$label:content=".&encode_utf8($field_content).'<br>';
                     eval {$field_MARC=MARC::Field->new(&encode_utf8($tag),&encode_utf8($field_content))};
                     if($@)
                     {print "<font color=\"red\">Error: MARC::Field->new routine some problem!!!</font><br>\n";exit};
                     
                     }
                  else
                     {if(&issubfield($label,$occ,'tail'))
                        {
                        $field_content=$fields{$label}->[$occ]->{'tail'}->[0];
                        #print $TOTALXMLFILE &encode_utf8($field_content);
                        #print  "&nbsp;$label:tail=".&encode_utf8($field_content).'<br>';
                        eval {$field_MARC=MARC::Field->new(&encode_utf8($tag),&encode_utf8($field_content))};
                        if($@)
                           {print "<font color=\"red\">Error: MARC::Field->new routine some problem!!!</font><br>\n";exit};
                        }
                     }                                          
                  
                  my $sub_occ=0;
                  foreach my $subs(sort keys %{$field})
                     {if($subs=~/\^/)
                        {my $code=$subs; $code=~s/\^//;
                        foreach my $sub_item(@{$field->{$subs}})
                           {                                
                           eval {$field_MARC = MARC::Field->new(&encode_utf8($tag),&encode_utf8($field_I1),&encode_utf8($field_I2),&encode_utf8($code),&encode_utf8($sub_item)) if $sub_occ==0};
                           if($@)
                              {print "<font color=\"red\">Error: MARC::Field->new routine some problem!!!</font><br>\n";exit};
                           eval {$field_MARC->add_subfields(&encode_utf8($code),&encode_utf8($sub_item)) if $sub_occ>0};
                           if($@)
                              {print "<font color=\"red\">Error: MARC::Field->add_subfields routine some problem!!!</font><br>\n";exit};                           
                           $sub_item=~s/\&/\&amp;/g;
                           $sub_item=~s/\&amp;([\w\d\#]+;)/\&$1/g;
                           $sub_item=~s/\x{1e}/\x{25b2}/gm;         # 30(1eh) -> 25b2h
                           $sub_item=~s/\x{15}/\x{a7}/gm;         # 21(15h) -> a7h
                           #$sub_item=~s/\ $/\&nbsp;/g;
                           #print "&nbsp;$label:^".&encode_utf8($code).'='.&encode_utf8($sub_item).'<br>';
                           #print $TOTALXMLFILE '<subfield code="'.&encode_utf8($code).'">'.&encode_utf8($sub_item).'</subfield>' if $v;
                           $sub_occ++;
                           }
                        }
                     }      
                  #
                  #print $TOTALXMLFILE '</field>'."\n" if $v; 
                  eval {$record_MARC->add_fields($field_MARC)}; 
                  
                  if($@)
                     {print "<font color=\"red\">Error: MARC::Record->add_fields routine some problem!!!</font><br>\n";exit};
                  }
               
               }  
            
                        
            #print $TOTALXMLFILE " </record>\n" if $v;   
            
            #$record_MARC->leader('         a              ');
            
            
            eval{print $MARCFILE $record_MARC->as_usmarc()};
            if($@)
               {print "<font color=\"red\">Error: MARC::Record->as_usmarc() routine some problem!!!</font><br>\n";exit};
            #print "<hr><br>\n";      
            }
            
close($MARCFILE);
#close($TOTALXMLFILE);