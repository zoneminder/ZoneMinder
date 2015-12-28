package ONVIF::PTZ::Types::G726DecOptions;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/schema' };

our $XML_ATTRIBUTE_CLASS;
undef $XML_ATTRIBUTE_CLASS;

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}

use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

Class::Std::initialize();

{ # BLOCK to scope variables

my %Bitrate_of :ATTR(:get<Bitrate>);
my %SampleRateRange_of :ATTR(:get<SampleRateRange>);

__PACKAGE__->_factory(
    [ qw(        Bitrate
        SampleRateRange

    ) ],
    {
        'Bitrate' => \%Bitrate_of,
        'SampleRateRange' => \%SampleRateRange_of,
    },
    {
        'Bitrate' => 'ONVIF::PTZ::Types::IntList',
        'SampleRateRange' => 'ONVIF::PTZ::Types::IntList',
    },
    {

        'Bitrate' => 'Bitrate',
        'SampleRateRange' => 'SampleRateRange',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::PTZ::Types::G726DecOptions

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
G726DecOptions from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * Bitrate


=item * SampleRateRange




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::PTZ::Types::G726DecOptions
   Bitrate =>  { # ONVIF::PTZ::Types::IntList
     Items =>  $some_value, # int
   },
   SampleRateRange =>  { # ONVIF::PTZ::Types::IntList
     Items =>  $some_value, # int
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

