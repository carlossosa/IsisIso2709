#!/usr/bin/perl -w

use strict;
use XML::Simple;
$XML::Simple::PREFERRED_PARSER='XML::Parser';
use Encode;
use utf8; # for utf8-strings in this code

# 1-ST ETAP: convert encoding
# 2-ND ETAP: move subfields, indicators

#list of known encoding:  'cp1251', 'utf-8', 'cp866' and maybe more

my %control_tags=(('001','0'),('005','0'));

my $marcxml_file;
my $marcxml_list_file;
if($ENV{'QUERY_STRING'})
   {($marcxml_file,$marcxml_list_file)=split(/\?/,$ENV{'QUERY_STRING'},2);}
else
   {$marcxml_file=$ARGV[0];$marcxml_list_file=$ARGV[1];}

my $prepared_marcxml_file=$marcxml_file.'_utf8.xml';

# 1-ST ETAP - convert encoding

my $xml_list_simple = XML::Simple->new();                        
my $xml_list_document;
eval {$xml_list_document = $xml_list_simple->XMLin($marcxml_list_file,forcearray => 1,forcecontent => 1)};
if($@)
   {print "<font color=\"red\">Error: XML::Simple routine can't read xml_list-file: <b>$marcxml_list_file</b>!!!</font><br>\n";exit};
#my @xml_list_records=@{$xml_list_document->{'record'}};
#my $number_of_record=scalar @records;   

#print $xml_list_document->{'encoding'}->[0]."\n";
if(exists $xml_list_document->{'encoding'})
   {
   my $encoding=$xml_list_document->{'encoding'}->[0];
   #print "Encoding tag: '$encoding'\n";
   unless(exists $encoding->{'source'})
      {print "ERROR: In <encoding> tag not present subtag <source> - don't know source encoding, EXIT!\n";exit;}   
   my $source_encoding=$encoding->{'source'}->[0];
   unless(exists $source_encoding->{'content'})
      {print "ERROR: subtag <encoding>-><source> empty - don't know source encoding, EXIT!\n";exit;}
   unless(exists $encoding->{'destination'})
      {print "ERROR: In <encoding> tag not present subtag <destination> - don't know destination encoding, EXIT!\n";exit;}
   my $destination_encoding=$encoding->{'destination'}->[0];
   unless(exists $destination_encoding->{'content'})
      {print "ERROR: subtag <encoding>-><destination> empty - don't know destination encoding, EXIT!\n";exit;}   
   $source_encoding=$source_encoding->{'content'};
   $destination_encoding=$destination_encoding->{'content'};
   unless($destination_encoding eq "utf-8")
      {print "ERROR: value of subtag <encoding>-><destination> support by now only 'utf-8' value, EXIT!\n";exit;}
   print "Source encoding: '$source_encoding'\n";
   print "Destination encoding: '$destination_encoding'\n";
   #unless($source_encoding eq $destination_encoding)
      {
      # read xml file and change encoding
      open(F, $marcxml_file) || die "$!";
      my @lines=<F>;
      my $lines_number=scalar @lines;
      print "Number of lines: $lines_number in file $marcxml_file\n";
      foreach(@lines)
         {
         $_=&decode($source_encoding,$_);
         }
      close F;
      open(R, '>'.$prepared_marcxml_file) || die "ERROR: $!";
      foreach(@lines)
         {print R encode($destination_encoding,$_);};      
      close R;
      }
   }
   else
   {print "WARNING: Tag <encoding> with sub-tag <source> and <destination> no set, try 'utf-8' encoding.\n";}

# 2ND ETAP - move subfields, indicators

my %source_destination;
my %destination_source;

if(exists $xml_list_document->{'label'})
   {
   print $xml_list_document->{'label'}."\n";
   my @lables=@{$xml_list_document->{'label'}};
   foreach  my $label_record(@lables) 
      {
      print $label_record."\n";
      my %label_hash=%$label_record;
      #print (keys %label_hash)."\n";
      my $source=$label_hash{'source'}->[0]->{'content'};
      my $destination=$label_hash{'destination'}->[0]->{'content'};
      print "source_destination($source\>$destination)\n";
      $source_destination{$source}=$destination;
      unless(exists $destination_source{$destination})
         {$destination_source{$destination}=$source;}
         else{print "ERROR: Found dublete destination field: '$destination'! BREAK PROGRAM!";exit;}
      }
   }
   
   

my %fields;
my $marker;

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
               if($@){print "<font color=\"red\">Error: &decode_utf8(".$control_hash{'tag'}.") have problem!!!</font><br> BREAK PROGRAM!\n";exit};
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
               if($@){print "<font color=\"red\">Error: &decode_utf8(".$field_hash->{'tag'}.") have problem!!!</font><br> BREAK PROGRAM!\n";exit};
               my $content='';            
               if(exists $field_hash->{'i1'}) 
                  {eval{$field=&decode_utf8($field_hash->{'i1'})}; 
                  if($@){print "<font color=\"red\">Error: &decode_utf8(".$field_hash->{'i1'}.") have problem!!!</font><br> BREAK PROGRAM!\n";exit};
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
                         if($@){print "<font color=\"red\">Error: &decode_utf8(".$subfield_hash->{'code'}.") have problem!!!</font><br> BREAK PROGRAM!\n";exit};
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
                  if($@){print "<font color=\"red\">Error: &decode_utf8(".$field_hash->{'content'}.") have problem!!!</font><br> BREAK PROGRAM!\n";exit};
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

my $subfields_delimiter='\^';
# ////////////// &print_xml_record($FILE,$number)
sub print_xml_record($$)
   {
   my $FILE=shift;
   my $number=shift;
   print $FILE ' <record';
   print $FILE ' number="'.&encode_utf8($number).'"';
   print $FILE '>'."\n";   
   print $FILE "  <leader>".&encode_utf8($marker)."</leader>\n";
   foreach my $label(sort keys %fields)
      {my $tag=$label; $tag='00'.$tag if (length $tag)==1; $tag='0'.$tag if (length $tag)==2;
      if(exists $control_tags{$label})
         {print $FILE "  <control tag=\"".&encode_utf8($tag)."\">".&encode_utf8($fields{$label}->[0]->{content}->[0])."</control>\n";   
         }else
         {my $occ=0;
         foreach my $field(@{$fields{$label}})
            {
            my $field_no_empty=0;
            $field_no_empty=1 if (length $fields{$label}->[$occ]->{'ind1'}->[0])>0; 
            #print "$label^ind1='".$fields{$label}->[$occ]->{'ind1'}->[0]."'\n";
            $field_no_empty=1 if (length $fields{$label}->[$occ]->{'ind2'}->[0])>0; 
            #print "$label^ind2='".$fields{$label}->[$occ]->{'ind2'}->[0]."'\n";
            foreach my $subs(sort keys %{$field})
               {
               if($subs=~/$subfields_delimiter/)
                  {
                  $field_no_empty=1 if (length $fields{$label}->[$occ]->{$subs}->[0])>0; 
                  #print "$label^$subs='".$fields{$label}->[$occ]->{$subs}->[0]."'\n";
                  }
               }                       
            print $FILE '  <field tag="'.&encode_utf8($tag).'"' if $field_no_empty;            
            if(&issubfield($label,$occ,'ind1'))
               {$fields{$label}->[$occ]->{'ind1'}->[0]=~s/\"/\&quot\;/g;
               print $FILE ' i1="'.&encode_utf8($fields{$label}->[$occ]->{'ind1'}->[0]).'"'}
               #else{print $FILE ' i1=""'}
            if(&issubfield($label,$occ,'ind2'))
               {$fields{$label}->[$occ]->{'ind2'}->[0]=~s/\"/\&quot\;/g;
               print $FILE ' i2="'.&encode_utf8($fields{$label}->[$occ]->{'ind2'}->[0]).'"'}
               #else{print $FILE ' i2=""'}
            print $FILE ">\n" if $field_no_empty;
            foreach my $subs(sort keys %{$field})
               {if($subs=~/$subfields_delimiter/)
                  {my $code=$subs; $code=~s/$subfields_delimiter//;                  
                  foreach my $sub_item(@{$field->{$subs}})
                     {
                     if((length $sub_item)>0)
                     {print $FILE '   <subfield code="'.&encode_utf8($code).'">';
                     print $FILE &encode_utf8($sub_item);
                     print $FILE "</subfield>\n"; 
                     }
                     }                    
                  }
               }
            #if(&issubfield($label,$occ,'tail'))
            #   {print $FILE $fields{$label}->[$occ]->{'tail'}->[0]}
            print $FILE "  </field>\n" if $field_no_empty; 
            $occ++;
            }
         }
      }   
   print $FILE " </record>\n\n";
   }
# \\\\\\\\\\\\\\ &print_xml_record($FILE,$number)

sub prepare_record()
   {
   foreach my $source(keys %source_destination)
      {
      my $destination=$source_destination{$source};
      unless($source eq $destination)
         {
         #print "Move($source->$destination)\n";
         #$fields{$label}->[$occ]->{'ind1'}->[0]
         #$fields{$label}->[$occ]->{'^a'}->[0]
         (my $source_tag,my $source_subs)=split(/\^/,$source);
         (my $destination_tag,my $destination_subs)=split(/\^/,$destination);
         print "Move('$source_tag'^'$source_subs'->'$destination_tag'^'$destination_subs')\n";
         if(exists $source_destination{$destination})
            {print "ERROR: found collision, move content $source to $destination but $destination exists!  BREAK PROGRAM!\n";exit;}
         unless($source_subs=~/ind/){$source_subs="^".$source_subs;}
         unless($destination_subs=~/ind/){$destination_subs="^".$destination_subs;}         
         my $occ=0;
         if(&issubfield($source_tag,$occ,$source_subs))
         {
         print "found to move\n";
         foreach my $field(@{$fields{$source_tag}})
            {
            my $sub_occ=0;
            foreach my $sub_item(@{$field->{$source_subs}})
               {
               my $value=$fields{$source_tag}->[$occ]->{$source_subs}->[$sub_occ];
               $fields{$destination_tag}->[$occ]->{$destination_subs}->[$sub_occ]=$value;
               undef $fields{$source_tag}->[$occ]->{$source_subs}->[$sub_occ];
               print "moved value '$value'\n";
               $sub_occ++;
               }                    
            $occ++;
            }
         }    
         }
      }
   }
   
my $xml_data_simple = XML::Simple->new();                        
my $xml_data_document;
eval {$xml_data_document = $xml_data_simple->XMLin($prepared_marcxml_file, forcearray => 1, forcecontent => 1)};
if($@)
   {print "<font color=\"red\">Error: XML::Simple routine can't read xml_data-file: <b>$prepared_marcxml_file</b>!!!</font><br> BREAK PROGRAM!\n";exit};
#my @xml_data_records=@{$xml_data_document->{'record'}};
#my $number_of_record=scalar @records;   

my @records=@{$xml_data_document->{'record'}};
my $number_of_record=scalar @records;
print "In file '$prepared_marcxml_file' found $number_of_record records\n";
my $xml_result_file=$prepared_marcxml_file."_prepared.xml";
open(my $XMLFILE, ">", $xml_result_file) or die "Can't open $xml_result_file: $!";
print "FILE $xml_result_file opened...\n";
my $collection_name=$xml_data_document->{'name'};
print $XMLFILE '<collection name="'.&encode_utf8($collection_name).'">'."\n";
foreach my $record_count(0..$number_of_record-1)
    {
    $Unimarc_Slim_record=$records[$record_count];
    print "extract field structure MARC xml slim for record ".($record_count+1)." (";
    &extract_field_structure_Unimarc_Slim_decode_utf8();  
    print "Ok)\n";
    &prepare_record();
    &print_xml_record($XMLFILE,$record_count+1);
    }
print $XMLFILE '</collection>';
close($XMLFILE);
print "FILE $xml_result_file closed.\n";
